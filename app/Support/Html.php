<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist sanitiser for the rich-text fields of official meeting minutes.
 *
 * Why a dedicated class instead of strip_tags():
 *
 *  1. strip_tags() never looks at attributes. `<div style="...">`, `<div id="x">`
 *     and `<a href=...>` all survive it intact, so a stored value could still
 *     smuggle CSS-based content spoofing or DOM clobbering into an official
 *     document — and into the LibreOffice converter that renders the PDF.
 *  2. Regex-based tag stripping cannot see entity-encoded payloads such as
 *     `o&#110;error`, which the browser decodes before it becomes a handler.
 *  3. Sanitising had been bound to a single FormRequest, so every other write
 *     path (seeders, imports, future endpoints) bypassed it entirely.
 *
 * This class is the single choke point. Every read path goes through the model
 * accessors, which call sanitize(), so sanitisation cannot be bypassed by
 * choosing a different write path.
 */
final class Html
{
    /**
     * Tags a minute-taker may use.
     *
     * `a` is included because minutes routinely link to the signed minutes or a
     * verification document; the attribute is then scheme-validated rather than
     * dropped wholesale (see isSafeHref()).
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
        'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'hr',
        'div', 'span', 'font', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'sup', 'sub',
    ];

    /**
     * Tags whose entire subtree is discarded, content included. These hold code
     * or nested documents, not prose, so keeping their text would leak markup
     * fragments into the rendered output.
     */
    private const DISCARD_SUBTREE_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'template',
        'noscript', 'svg', 'math', 'head', 'title', 'form', 'select', 'option',
    ];

    /**
     * Attributes kept on any allowed element.
     */
    private const ALLOWED_ATTRIBUTES = [
        'colspan', 'rowspan',
    ];

    /**
     * Attributes kept only on <a>. `href` is scheme-checked before it survives.
     */
    private const ANCHOR_ATTRIBUTES = [
        'href', 'target', 'title',
    ];

    /**
     * Attributes kept only on <font>. All three are purely presentational, which is
     * what the editor's font-size and colour pickers emit.
     */
    private const FONT_ATTRIBUTES = [
        'color', 'size', 'face',
    ];

    /**
     * URL schemes a link may use. Anything else — javascript:, data:, vbscript: —
     * is stripped, so the link survives but the payload does not.
     */
    private const ALLOWED_URL_SCHEMES = [
        'http', 'https', 'mailto', 'tel',
    ];

    /**
     * CSS properties kept in a style attribute. The editor needs these three for
     * text colour, highlighting and paragraph alignment; everything else —
     * position, z-index, background-image, content — is a spoofing or
     * data-exfiltration vector and is dropped.
     */
    private const ALLOWED_STYLE_PROPERTIES = [
        'color', 'background-color', 'text-align',
    ];

    /**
     * Sanitise user-authored rich text.
     *
     * @param  string|null  $html  Raw value as stored.
     * @return string|null Whitelisted markup, or null when there is nothing to render.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        if (trim($html) === '') {
            return null;
        }

        if (! class_exists(DOMDocument::class)) {
            // ext-dom is part of a standard PHP build, but degrade to a
            // conservative fallback rather than fataling on an official document.
            return self::fallbackSanitize($html);
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        // The wrapper keeps libxml from promoting stray text to a document root,
        // and the explicit encoding declaration stops UTF-8 being mangled.
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8" ?><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $document->documentElement === null) {
            return null;
        }

        $modified = false;
        self::scrub($document->documentElement, $modified);

        /*
         * Only re-serialise when something actually had to change.
         *
         * libxml normalises markup on the way out (attribute order, entity
         * spelling, self-closing forms). Returning the original bytes for content
         * that was already clean keeps official minutes byte-identical to what the
         * minute-taker typed, and avoids any chance of the sanitiser quietly
         * reformatting a document of record.
         */
        if (! $modified) {
            return $html;
        }

        $output = '';
        foreach ($document->documentElement->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        $output = trim($output);

        return $output === '' ? null : $output;
    }

    /**
     * Recursively drop or unwrap anything outside the whitelist.
     *
     * @param  bool  $modified  Set to true whenever the tree is altered.
     */
    private static function scrub(DOMNode $node, bool &$modified): void
    {
        // Snapshot the child list: removing nodes mutates the live NodeList.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);
                $modified = true;
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DISCARD_SUBTREE_TAGS, true)) {
                $node->removeChild($child);
                $modified = true;
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown but harmless container: keep the prose, drop the wrapper.
                self::scrub($child, $modified);
                $modified = true;

                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);
                continue;
            }

            self::filterAttributes($child, $modified);
            self::scrub($child, $modified);
        }
    }

    /**
     * Strip every attribute that is not explicitly permitted, and narrow any
     * surviving style attribute to the safe property list.
     */
    private static function filterAttributes(DOMElement $element, bool &$modified): void
    {
        $tag = strtolower($element->nodeName);

        $attributes = [];

        foreach ($element->attributes ?? [] as $attribute) {
            $attributes[$attribute->nodeName] = $attribute->nodeValue;
        }

        foreach ($attributes as $name => $value) {
            $name = strtolower($name);

            if ($name === 'style') {
                $safe = self::filterStyle((string) $value);

                if ($safe === null) {
                    $element->removeAttribute('style');
                    $modified = true;
                } elseif ($safe !== trim((string) $value)) {
                    $element->setAttribute('style', $safe);
                    $modified = true;
                }

                continue;
            }

            if ($name === 'href' && $tag === 'a') {
                if (self::isSafeHref((string) $value)) {
                    // A link opening a new tab must not hand window.opener to the
                    // destination (reverse tabnabbing).
                    if (strtolower((string) $element->getAttribute('target')) === '_blank') {
                        $rel = trim((string) $element->getAttribute('rel'));
                        $parts = $rel === '' ? [] : (array) preg_split('/\s+/', $rel);
                        $parts = array_values(array_unique(array_filter(array_merge($parts, ['noopener', 'noreferrer']))));
                        $newRel = implode(' ', $parts);

                        if ($newRel !== $rel) {
                            $element->setAttribute('rel', $newRel);
                            $modified = true;
                        }
                    }
                } else {
                    $element->removeAttribute('href');
                    $modified = true;
                }

                continue;
            }

            if ($name === 'color' && $tag === 'font') {
                if (self::isSafeColor((string) $value)) {
                    continue;
                }

                $element->removeAttribute('color');
                $modified = true;
                continue;
            }

            $elementSpecific = match ($tag) {
                'a' => in_array($name, self::ANCHOR_ATTRIBUTES, true),
                'font' => in_array($name, self::FONT_ATTRIBUTES, true),
                default => false,
            };

            if ($elementSpecific || in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                continue;
            }

            // on*, src, id, name, formaction, data-*, xlink:href — all out.
            $element->removeAttribute($name);
            $modified = true;
        }
    }

    /**
     * Accept a relative URL, or one whose scheme is on the allowlist.
     *
     * Dropping href outright would be simpler, but minutes legitimately link to the
     * signed minutes and to verification documents. The danger is the scheme, not
     * the attribute, so the scheme is what gets checked.
     */
    private static function isSafeHref(string $href): bool
    {
        $href = trim($href);

        if ($href === '') {
            return false;
        }

        // Control characters are used to smuggle a scheme past this check
        // ("java\0script:", "java\tscript:").
        if (preg_match('/[\x00-\x1F\x7F]/', $href)) {
            return false;
        }

        // A relative URL carries no scheme and cannot execute script, so
        // parse_url() reporting null for the scheme is the expected case.
        $scheme = parse_url($href, PHP_URL_SCHEME);

        if ($scheme === null || $scheme === false || $scheme === '') {
            return true;
        }

        return in_array(strtolower($scheme), self::ALLOWED_URL_SCHEMES, true);
    }

    /**
     * Keep only the permitted CSS declarations, and only with safe values.
     */
    private static function filterStyle(?string $style): ?string
    {
        if ($style === null || trim($style) === '') {
            return null;
        }

        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if (! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                continue;
            }

            $value = self::sanitizeStyleValue($property, $value);

            if ($value !== null) {
                $safe[] = $property . ': ' . $value;
            }
        }

        return $safe === [] ? null : implode('; ', $safe);
    }

    /**
     * Reject any value that could reference an external resource or run code,
     * then confirm the value fits the property it was declared on.
     */
    private static function sanitizeStyleValue(string $property, string $value): ?string
    {
        // url(), expression(), @import, data: URIs and HTML entities have no
        // legitimate use in a meeting minute.
        if (preg_match('/(url\s*\(|expression\s*\(|@import|javascript:|data:|&#)/i', $value)) {
            return null;
        }

        return match ($property) {
            'text-align' => in_array(strtolower($value), ['left', 'center', 'right', 'justify'], true)
                ? strtolower($value)
                : null,
            'color', 'background-color' => self::isSafeColor($value) ? $value : null,
            default => null,
        };
    }

    /**
     * Accept hex, rgb()/rgba() with numeric channels, or a bare CSS colour keyword.
     * Anything with punctuation or a URL shape has already been rejected above.
     */
    private static function isSafeColor(string $value): bool
    {
        $value = trim($value);

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
            return true;
        }

        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/i', $value)) {
            return true;
        }

        return (bool) preg_match('/^[a-z]{1,24}$/i', $value);
    }

    /**
     * Conservative fallback used only when ext-dom is unavailable.
     *
     * strip_tags() still leaves attributes behind, so event handlers and
     * javascript: URLs are removed afterwards. This path is deliberately cruder
     * than filterAttributes() — it cannot narrow a style attribute, so those are
     * dropped outright — but it keeps the page safe when the preferred path is
     * unavailable.
     */
    private static function fallbackSanitize(string $html): ?string
    {
        $allowed = '<' . implode('><', self::ALLOWED_TAGS) . '>';

        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        // Keep only allow-listed href schemes rather than dropping every link.
        $clean = preg_replace_callback(
            '/\shref\s*=\s*("([^"]*)"|\'([^\']*)\')/i',
            static function (array $m): string {
                $url = $m[2] ?? $m[3] ?? '';

                return self::isSafeHref($url) ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '';
            },
            (string) $clean
        );

        $clean = preg_replace('/\s(src|formaction)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $clean);
        $clean = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $clean);

        $clean = trim((string) $clean);

        return $clean === '' ? null : $clean;
    }
}
