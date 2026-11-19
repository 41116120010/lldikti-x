<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use PhpOffice\PhpWord\SimpleType\LineSpacingRule;
use PhpOffice\PhpWord\SimpleType\TblWidth;

/**
 * Shared HTML-to-PhpWord renderer for official LLDIKTI documents
 * (Berita Acara & Surat Edaran).
 */
final class DocxHtmlRenderer
{
    private const BLOCK_TAGS = [
        'p', 'div', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote', 'hr', 'table',
    ];

    private const FONT_SIZE_MAP = [
        1 => 8.0,
        2 => 10.0,
        3 => 12.0,
        4 => 14.0,
        5 => 18.0,
        6 => 24.0,
        7 => 36.0,
    ];

    private const NAMED_COLORS = [
        'black' => '000000',
        'white' => 'FFFFFF',
        'red' => 'DC2626',
        'blue' => '1D4ED8',
        'navy' => '1E3A8A',
        'green' => '059669',
        'gray' => '64748B',
        'grey' => '64748B',
        'slate' => '334155',
    ];

    /**
     * Split sanitised HTML into sequential segments: prose blocks vs top-level tables.
     * Ensures <table> elements never end up nested inside a bordered prose box table.
     *
     * @return list<array{type: 'prose'|'table', nodes?: list<DOMNode>, node?: DOMElement}>
     */
    public static function segmentBlocks(?string $html): array
    {
        $clean = trim((string) $html);
        if ($clean === '') {
            return [];
        }

        $root = self::parseRoot($clean);
        if ($root === null) {
            return [];
        }

        $flattened = [];
        self::collectTopLevelBlocks($root, $flattened);

        $segments = [];
        $proseBuffer = [];

        foreach ($flattened as $node) {
            if ($node instanceof DOMElement && strtolower($node->tagName) === 'table') {
                if ($proseBuffer !== []) {
                    $segments[] = ['type' => 'prose', 'nodes' => $proseBuffer];
                    $proseBuffer = [];
                }
                $segments[] = ['type' => 'table', 'node' => $node];

                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE && trim($node->nodeValue ?? '') === '') {
                if ($proseBuffer === []) {
                    continue;
                }
            }

            $proseBuffer[] = $node;
        }

        if ($proseBuffer !== []) {
            $segments[] = ['type' => 'prose', 'nodes' => $proseBuffer];
        }

        return $segments;
    }

    /**
     * Render raw/sanitised HTML directly into a PhpWord container (Section or Cell).
     *
     * @param  array<string,mixed>  $options
     */
    public static function renderHtml(mixed $container, ?string $html, array $options = []): void
    {
        $clean = trim((string) $html);
        if ($clean === '') {
            return;
        }

        $root = self::parseRoot($clean);
        if ($root === null) {
            $fallback = self::xmlSafe(trim(strip_tags($clean)));
            if ($fallback !== '') {
                $container->addText($fallback, self::baseFontStyle($options), self::paragraphStyle($options));
            }

            return;
        }

        $nodes = [];
        foreach ($root->childNodes as $child) {
            $nodes[] = $child;
        }

        self::renderNodes($container, $nodes, $options);
    }

    /**
     * Render a list of DOMNodes into a PhpWord container (Section or Cell).
     *
     * @param  list<DOMNode>  $nodes
     * @param  array<string,mixed>  $options
     */
    public static function renderNodes(mixed $container, array $nodes, array $options = []): void
    {
        $inlineBuffer = [];
        $renderedAny = false;

        $flushInline = static function () use (&$inlineBuffer, $container, $options, &$renderedAny): void {
            if ($inlineBuffer === []) {
                return;
            }

            if (self::hasVisibleInlineContent($inlineBuffer)) {
                $run = $container->addTextRun(self::paragraphStyle($options));
                $font = self::baseFontStyle($options);
                foreach ($inlineBuffer as $inlineNode) {
                    self::appendInlineNode($run, $inlineNode, $font);
                }
                $renderedAny = true;
            }

            $inlineBuffer = [];
        };

        $total = count($nodes);
        foreach ($nodes as $index => $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);

                if (in_array($tag, self::BLOCK_TAGS, true)) {
                    $flushInline();
                    $isLast = self::isEffectivelyLastBlock($nodes, $index, $total);

                    if ($tag === 'table') {
                        // Tables inside a prose container should only appear via segmentBlocks() at section level,
                        // or flattened as text rows when nested inside a cell.
                        if (! ($options['allowNestedTables'] ?? false)) {
                            self::renderTableAsFallbackParagraphs($container, $node, $options);
                            $renderedAny = true;
                        }
                    } else {
                        self::renderBlockElement($container, $node, $tag, $options, $isLast);
                        $renderedAny = true;
                    }

                    continue;
                }
            }

            $inlineBuffer[] = $node;
        }

        $flushInline();

        if (! $renderedAny && ($options['requireParagraph'] ?? false)) {
            $container->addText('', self::baseFontStyle($options), self::paragraphStyle($options, spaceAfterPt: 0));
        }
    }

    /**
     * Render a DOM <table> as a top-level full-width fixed-grid PhpWord table.
     *
     * @param  array<string,mixed>  $options
     */
    public static function renderTable(mixed $section, DOMElement $tableNode, float $contentWidthCm, array $options = []): void
    {
        $rows = self::collectTableRows($tableNode);
        if ($rows === []) {
            return;
        }

        $maxCols = 1;
        foreach ($rows as $rowMeta) {
            $colSum = 0;
            foreach ($rowMeta['cells'] as $cell) {
                $colSum += max(1, (int) ($cell->getAttribute('colspan') ?: 1));
            }
            $maxCols = max($maxCols, $colSum);
        }

        $totalTwips = DocumentLayout::cmToTwips($contentWidthCm);
        $baseColTwips = (int) floor($totalTwips / $maxCols);
        $colTwips = array_fill(0, $maxCols, $baseColTwips);
        $colTwips[$maxCols - 1] += $totalTwips - ($baseColTwips * $maxCols);

        $tableStyle = [
            'unit' => TblWidth::TWIP,
            'width' => $totalTwips,
            'layout' => 'fixed',
            'borderSize' => DocumentLayout::ptToBorderEighths(DocumentLayout::BORDER_PT),
            'borderColor' => DocumentLayout::BORDER_COLOR,
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 90,
            'cellMarginRight' => 90,
        ];

        $table = $section->addTable($tableStyle);

        $activeRowSpans = array_fill(0, $maxCols, 0);

        foreach ($rows as $rowIndex => $rowMeta) {
            $rowStyle = ['cantSplit' => true];
            $isHeaderRow = $rowMeta['isHeader'] || ($rowIndex === 0 && self::rowIsAllTh($rowMeta['cells']));
            if ($isHeaderRow) {
                $rowStyle['tblHeader'] = true;
            }

            $phpRow = $table->addRow(null, $rowStyle);
            $domCells = $rowMeta['cells'];
            $domIdx = 0;
            $colCursor = 0;

            while ($colCursor < $maxCols) {
                if ($activeRowSpans[$colCursor] > 0) {
                    $activeRowSpans[$colCursor]--;
                    $phpRow->addCell($colTwips[$colCursor], [
                        'vMerge' => 'continue',
                        'vAlign' => 'center',
                    ])->addText('', self::baseFontStyle($options), self::paragraphStyle($options, spaceAfterPt: 0));
                    $colCursor++;

                    continue;
                }

                if (! isset($domCells[$domIdx])) {
                    $phpRow->addCell($colTwips[$colCursor], ['vAlign' => 'top'])
                        ->addText('', self::baseFontStyle($options), self::paragraphStyle($options, spaceAfterPt: 0));
                    $colCursor++;

                    continue;
                }

                $domCell = $domCells[$domIdx++];
                $colSpan = max(1, (int) ($domCell->getAttribute('colspan') ?: 1));
                $colSpan = min($colSpan, $maxCols - $colCursor);
                $rowSpan = max(1, (int) ($domCell->getAttribute('rowspan') ?: 1));

                $cellWidthTwips = 0;
                for ($i = 0; $i < $colSpan; $i++) {
                    $cellWidthTwips += $colTwips[$colCursor + $i];
                    if ($rowSpan > 1) {
                        $activeRowSpans[$colCursor + $i] = $rowSpan - 1;
                    }
                }

                $isTh = strtolower($domCell->tagName) === 'th';
                $cellStyle = [
                    'vAlign' => $isTh ? 'center' : 'top',
                ];
                if ($colSpan > 1) {
                    $cellStyle['gridSpan'] = $colSpan;
                }
                if ($rowSpan > 1) {
                    $cellStyle['vMerge'] = 'restart';
                }
                if ($isTh) {
                    $cellStyle['shading'] = ['fill' => DocumentLayout::ATTENDANCE_HEADER_FILL];
                }

                $phpCell = $phpRow->addCell($cellWidthTwips, $cellStyle);

                $cellOptions = array_merge($options, [
                    'fontSizePt' => min((float) ($options['fontSizePt'] ?? 12.0), 11.0),
                    'linePt' => isset($options['linePt']) ? min((float) $options['linePt'], 15.0) : null,
                    'defaultAlign' => self::extractAlignment($domCell, $isTh ? 'left' : 'left'),
                    'defaultBold' => $isTh,
                    'spaceAfterPt' => 0.0,
                    'requireParagraph' => true,
                    'allowNestedTables' => false,
                ]);

                $cellChildren = [];
                foreach ($domCell->childNodes as $child) {
                    $cellChildren[] = $child;
                }
                self::renderNodes($phpCell, $cellChildren, $cellOptions);

                $colCursor += $colSpan;
            }
        }
    }

    private static function parseRoot(string $html): ?DOMElement
    {
        if (! class_exists(DOMDocument::class)) {
            return null;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="utf-8" ?><div id="docx-html-root">'.$html.'</div>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === 'docx-html-root') {
                return $div;
            }
        }

        return $dom->documentElement instanceof DOMElement ? $dom->documentElement : null;
    }

    /**
     * Flatten blocks containing nested <table> so tables always surface at top level.
     *
     * @param  list<DOMNode>  $out
     */
    private static function collectTopLevelBlocks(DOMNode $parent, array &$out): void
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) !== 'table' && self::containsTableDescendant($child)) {
                self::collectTopLevelBlocks($child, $out);

                continue;
            }

            $out[] = $child;
        }
    }

    private static function containsTableDescendant(DOMElement $element): bool
    {
        return $element->getElementsByTagName('table')->length > 0;
    }

    /**
     * @param  array<string,mixed>  $options
     */
    private static function renderBlockElement(mixed $container, DOMElement $element, string $tag, array $options, bool $isLast): void
    {
        if ($tag === 'hr') {
            $container->addText(
                '____________________________________________________________',
                ['name' => $options['fontName'] ?? 'Times New Roman', 'size' => 8, 'color' => '94A3B8'],
                ['alignment' => 'center', 'spaceBefore' => 40, 'spaceAfter' => $isLast ? 0 : 60]
            );

            return;
        }

        if ($tag === 'ul' || $tag === 'ol') {
            self::renderList($container, $element, $tag, 0, $options, $isLast);

            return;
        }

        if ($tag === 'blockquote') {
            $pStyle = self::paragraphStyle($options, align: self::extractAlignment($element, 'left'), spaceBeforePt: 2, spaceAfterPt: $isLast ? 0 : 4);
            $pStyle['indentation'] = ['left' => 360];
            $run = $container->addTextRun($pStyle);
            $font = array_merge(self::baseFontStyle($options), ['italic' => true, 'color' => '334155']);
            self::appendInlineChildren($run, $element, $font);

            return;
        }

        if (in_array($tag, ['h2', 'h3', 'h4'], true)) {
            $basePt = (float) ($options['fontSizePt'] ?? 12.0);
            $sizePt = match ($tag) {
                'h2' => $basePt + 2.0,
                'h3' => $basePt + 1.0,
                default => $basePt,
            };
            $align = self::extractAlignment($element, 'left');
            $pStyle = self::paragraphStyle($options, align: $align, spaceBeforePt: 4, spaceAfterPt: $isLast ? 0 : 3);
            $font = array_merge(self::baseFontStyle($options), ['size' => $sizePt, 'bold' => true]);
            $run = $container->addTextRun($pStyle);
            self::appendInlineChildren($run, $element, $font);

            return;
        }

        // <p> or <div>: if it contains child block elements, recurse; otherwise emit one paragraph run.
        if (self::hasDirectBlockChild($element)) {
            $children = [];
            foreach ($element->childNodes as $child) {
                $children[] = $child;
            }
            $childAlign = self::extractAlignment($element, $options['defaultAlign'] ?? 'both');
            self::renderNodes($container, $children, array_merge($options, ['defaultAlign' => $childAlign]));

            return;
        }

        if (! self::hasVisibleInlineContent(iterator_to_array($element->childNodes))) {
            return;
        }

        $align = self::extractAlignment($element, $options['defaultAlign'] ?? 'both');
        $spaceAfter = $isLast ? 0.0 : (float) ($options['spaceAfterPt'] ?? 4.0);
        $pStyle = self::paragraphStyle($options, align: $align, spaceBeforePt: 0.0, spaceAfterPt: $spaceAfter);
        $font = self::applyElementStyles(self::baseFontStyle($options), $element);
        $run = $container->addTextRun($pStyle);
        self::appendInlineChildren($run, $element, $font);
    }

    /**
     * @param  array<string,mixed>  $options
     */
    private static function renderList(mixed $container, DOMElement $listNode, string $listTag, int $level, array $options, bool $isLast): void
    {
        $items = [];
        foreach ($listNode->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                $items[] = $child;
            }
        }

        $bullets = ['• ', '◦ ', '▪ '];
        $bullet = $bullets[$level % count($bullets)];
        $itemCount = count($items);
        $num = 1;

        foreach ($items as $idx => $li) {
            $prefix = $listTag === 'ol' ? ($num++.'. ') : $bullet;
            $inlineChildren = [];
            $nestedLists = [];

            foreach ($li->childNodes as $liChild) {
                if ($liChild instanceof DOMElement && in_array(strtolower($liChild->tagName), ['ul', 'ol'], true)) {
                    $nestedLists[] = $liChild;
                } else {
                    $inlineChildren[] = $liChild;
                }
            }

            $isVeryLast = $isLast && ($idx === $itemCount - 1) && $nestedLists === [];
            $itemSpaceAfter = $isVeryLast ? 0.0 : (float) ($options['listSpaceAfterPt'] ?? 2.0);
            $pStyle = self::paragraphStyle(
                $options,
                align: self::extractAlignment($li, $options['defaultAlign'] ?? 'both'),
                spaceBeforePt: 0.0,
                spaceAfterPt: $itemSpaceAfter
            );
            $leftStep = (int) ($options['listLeftStepTwips'] ?? 360);
            $hanging = (int) ($options['listHangingTwips'] ?? 220);
            $pStyle['indentation'] = [
                'left' => $leftStep * ($level + 1),
                'hanging' => $hanging,
            ];

            $font = self::applyElementStyles(self::baseFontStyle($options), $li);
            $run = $container->addTextRun($pStyle);
            $run->addText(self::xmlSafe($prefix), $font);
            foreach ($inlineChildren as $inlineChild) {
                self::appendInlineNode($run, $inlineChild, $font);
            }

            $nestedCount = count($nestedLists);
            foreach ($nestedLists as $nIdx => $nested) {
                self::renderList(
                    $container,
                    $nested,
                    strtolower($nested->tagName),
                    $level + 1,
                    $options,
                    $isLast && ($idx === $itemCount - 1) && ($nIdx === $nestedCount - 1)
                );
            }
        }
    }

    /**
     * @param  array<string,mixed>  $fontStyle
     */
    private static function appendInlineChildren(mixed $run, DOMNode $parent, array $fontStyle): void
    {
        foreach ($parent->childNodes as $child) {
            self::appendInlineNode($run, $child, $fontStyle);
        }
    }

    /**
     * @param  array<string,mixed>  $fontStyle
     */
    private static function appendInlineNode(mixed $run, DOMNode $node, array $fontStyle): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $raw = $node->nodeValue ?? '';
            $normalized = preg_replace('/[ \t\r\n]+/u', ' ', $raw) ?? '';
            if ($normalized !== '') {
                $run->addText(self::xmlSafe($normalized), $fontStyle);
            }

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'br') {
            $run->addTextBreak();

            return;
        }

        $nextStyle = self::applyElementStyles($fontStyle, $node);

        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            $label = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
            if ($label === '') {
                $label = $href;
            }

            if ($href !== '' && self::isSafeExternalLink($href)) {
                $linkStyle = array_merge($nextStyle, [
                    'color' => '1D4ED8',
                    'underline' => 'single',
                ]);
                $run->addLink(self::xmlSafe($href), self::xmlSafe($label), $linkStyle);

                return;
            }
        }

        self::appendInlineChildren($run, $node, $nextStyle);
    }

    /**
     * @param  array<string,mixed>  $baseStyle
     * @return array<string,mixed>
     */
    private static function applyElementStyles(array $baseStyle, DOMElement $element): array
    {
        $tag = strtolower($element->tagName);
        $style = $baseStyle;

        match ($tag) {
            'b', 'strong' => $style['bold'] = true,
            'i', 'em' => $style['italic'] = true,
            'u' => $style['underline'] = 'single',
            's', 'del', 'strike' => $style['strikethrough'] = true,
            'sup' => $style['superScript'] = true,
            'sub' => $style['subScript'] = true,
            default => null,
        };

        if ($tag === 'font') {
            if ($element->hasAttribute('color')) {
                $hex = self::parseColor($element->getAttribute('color'));
                if ($hex !== null) {
                    $style['color'] = $hex;
                }
            }
            if ($element->hasAttribute('size')) {
                $sizeKey = (int) $element->getAttribute('size');
                if (isset(self::FONT_SIZE_MAP[$sizeKey])) {
                    $style['size'] = self::FONT_SIZE_MAP[$sizeKey];
                }
            }
        }

        if ($element->hasAttribute('style')) {
            foreach (explode(';', $element->getAttribute('style')) as $decl) {
                if (! str_contains($decl, ':')) {
                    continue;
                }
                [$prop, $val] = array_map('trim', explode(':', $decl, 2));
                $prop = strtolower($prop);
                $valLower = strtolower($val);

                if ($prop === 'color') {
                    $hex = self::parseColor($val);
                    if ($hex !== null) {
                        $style['color'] = $hex;
                    }
                } elseif ($prop === 'font-weight' && in_array($valLower, ['bold', 'bolder', '600', '700', '800'], true)) {
                    $style['bold'] = true;
                } elseif ($prop === 'font-style' && $valLower === 'italic') {
                    $style['italic'] = true;
                } elseif ($prop === 'text-decoration') {
                    if (str_contains($valLower, 'underline')) {
                        $style['underline'] = 'single';
                    }
                    if (str_contains($valLower, 'line-through')) {
                        $style['strikethrough'] = true;
                    }
                } elseif ($prop === 'font-size') {
                    if (preg_match('/^(\d+(?:\.\d+)?)\s*(pt|px)$/i', $valLower, $m)) {
                        $num = (float) $m[1];
                        $pt = $m[2] === 'px' ? round($num * 0.75, 1) : $num;
                        if ($pt >= 6.0 && $pt <= 48.0) {
                            $style['size'] = $pt;
                        }
                    }
                }
            }
        }

        return $style;
    }

    private static function extractAlignment(DOMElement $element, string $fallback = 'both'): string
    {
        $raw = '';
        if ($element->hasAttribute('style') && preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $element->getAttribute('style'), $m)) {
            $raw = strtolower($m[1]);
        } elseif ($element->hasAttribute('align')) {
            $raw = strtolower(trim($element->getAttribute('align')));
        }

        return match ($raw) {
            'left' => 'left',
            'center' => 'center',
            'right' => 'right',
            'justify', 'both' => 'both',
            default => $fallback,
        };
    }

    private static function parseColor(string $value): ?string
    {
        $v = trim($value);
        if (preg_match('/^#([0-9a-f]{6})$/i', $v, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/^#([0-9a-f]{3})$/i', $v, $m)) {
            $h = strtoupper($m[1]);

            return $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
        }
        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $v, $m)) {
            return sprintf('%02X%02X%02X', min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3]));
        }

        return self::NAMED_COLORS[strtolower($v)] ?? null;
    }

    private static function isSafeExternalLink(string $href): bool
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $href)) {
            return false;
        }
        $scheme = parse_url($href, PHP_URL_SCHEME);
        if ($scheme === null || $scheme === false || $scheme === '') {
            return true;
        }

        return in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
    }

    /**
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    private static function baseFontStyle(array $options): array
    {
        $font = [
            'name' => $options['fontName'] ?? 'Times New Roman',
            'size' => (float) ($options['fontSizePt'] ?? 12.0),
        ];

        if (! empty($options['defaultBold'])) {
            $font['bold'] = true;
        }

        return $font;
    }

    /**
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    private static function paragraphStyle(
        array $options,
        ?string $align = null,
        float $spaceBeforePt = 0.0,
        ?float $spaceAfterPt = null,
    ): array {
        $resolvedAfterPt = $spaceAfterPt ?? (float) ($options['spaceAfterPt'] ?? 0.0);
        $beforeTwips = DocumentLayout::ptToTwips($spaceBeforePt);
        $afterTwips = DocumentLayout::ptToTwips($resolvedAfterPt);

        $para = [
            'alignment' => $align ?? ($options['defaultAlign'] ?? 'both'),
            'spaceBefore' => $beforeTwips,
            'spaceAfter' => $afterTwips,
        ];

        if (isset($options['linePt']) && $options['linePt'] !== null) {
            $para['spacing'] = [
                'before' => $beforeTwips,
                'after' => $afterTwips,
                'line' => DocumentLayout::ptToTwips((float) $options['linePt']),
                'lineRule' => LineSpacingRule::EXACT,
            ];
        } elseif (isset($options['lineHeight'])) {
            $para['lineHeight'] = (float) $options['lineHeight'];
        }

        return $para;
    }

    /**
     * @return list<array{isHeader: bool, cells: list<DOMElement>}>
     */
    private static function collectTableRows(DOMElement $tableNode): array
    {
        $rows = [];
        foreach ($tableNode->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if ($tag === 'tr') {
                $cells = self::extractRowCells($child);
                if ($cells !== []) {
                    $rows[] = ['isHeader' => false, 'cells' => $cells];
                }
            } elseif (in_array($tag, ['thead', 'tbody', 'tfoot'], true)) {
                $isHead = $tag === 'thead';
                foreach ($child->childNodes as $sub) {
                    if ($sub instanceof DOMElement && strtolower($sub->tagName) === 'tr') {
                        $cells = self::extractRowCells($sub);
                        if ($cells !== []) {
                            $rows[] = ['isHeader' => $isHead, 'cells' => $cells];
                        }
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @return list<DOMElement>
     */
    private static function extractRowCells(DOMElement $tr): array
    {
        $cells = [];
        foreach ($tr->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['td', 'th'], true)) {
                $cells[] = $child;
            }
        }

        return $cells;
    }

    /**
     * @param  list<DOMElement>  $cells
     */
    private static function rowIsAllTh(array $cells): bool
    {
        if ($cells === []) {
            return false;
        }
        foreach ($cells as $cell) {
            if (strtolower($cell->tagName) !== 'th') {
                return false;
            }
        }

        return true;
    }

    /**
     * Fallback when a <table> appears nested inside another table cell.
     *
     * @param  array<string,mixed>  $options
     */
    private static function renderTableAsFallbackParagraphs(mixed $container, DOMElement $tableNode, array $options): void
    {
        foreach (self::collectTableRows($tableNode) as $rowMeta) {
            $parts = [];
            foreach ($rowMeta['cells'] as $cell) {
                $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent) ?? '');
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
            if ($parts !== []) {
                $container->addText(
                    self::xmlSafe(implode(' | ', $parts)),
                    self::baseFontStyle($options),
                    self::paragraphStyle($options, align: 'left', spaceAfterPt: 2.0)
                );
            }
        }
    }

    private static function hasDirectBlockChild(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), self::BLOCK_TAGS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<DOMNode>  $nodes
     */
    private static function hasVisibleInlineContent(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE && trim($node->nodeValue ?? '') !== '') {
                return true;
            }
            if ($node instanceof DOMElement) {
                if (strtolower($node->tagName) === 'br' || trim($node->textContent) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<DOMNode>  $nodes
     */
    private static function isEffectivelyLastBlock(array $nodes, int $index, int $total): bool
    {
        for ($i = $index + 1; $i < $total; $i++) {
            $next = $nodes[$i];
            if ($next instanceof DOMElement) {
                return false;
            }
            if ($next->nodeType === XML_TEXT_NODE && trim($next->nodeValue ?? '') !== '') {
                return false;
            }
        }

        return true;
    }

    public static function xmlSafe(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
