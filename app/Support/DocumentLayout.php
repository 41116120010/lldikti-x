<?php

namespace App\Support;

/**
 * The single source of truth for Berita Acara document layout.
 *
 * Both renderers read their numbers from here: the HTML template that Dompdf
 * turns into the PDF, and the PhpWord writer that produces the .docx. Neither
 * owns a value of its own.
 *
 * That constraint is the whole point. The two formats were previously built
 * from the same markup by two unrelated renderers - Dompdf and Word's own HTML
 * importer - so any number only one of them understood was silently ignored by
 * the other. Margins vanished, column widths collapsed, images failed to
 * decode. Giving each renderer its own writer, fed from one table of values,
 * removes the class of bug where the two documents quietly disagree.
 *
 * Lengths are in points because that is the unit both formats share without
 * conversion. A4 portrait is 595.28 x 841.89 pt.
 */
final class DocumentLayout
{
    // ---------------------------------------------------------------------
    // Paper and margins
    // ---------------------------------------------------------------------

    public const A4_WIDTH_PT = 595.28;

    public const A4_HEIGHT_PT = 841.89;

    public const A4_WIDTH_CM = 21.0;

    public const A4_HEIGHT_CM = 29.7;

    public const TOP_MARGIN_CM = 1.0;

    public const RIGHT_MARGIN_CM = 2.0;

    public const BOTTOM_MARGIN_CM = 2.5;

    public const LEFT_MARGIN_CM = 3.0;

    public const CONTENT_WIDTH_CM = 16.0;

    public const DOCX_FIRST_HEADER_MARGIN_TOP_TWIPS = 333;

    public const DOCX_FIRST_HEADER_HEIGHT_TWIPS = 142;

    public const DOCX_MARGIN_RIGHT_TWIPS = 1134;

    public const DOCX_MARGIN_BOTTOM_TWIPS = 1418;

    public const DOCX_MARGIN_LEFT_TWIPS = 1701;

    /**
     * Page margins in centimetres: top, right, bottom, left.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function marginsCm(): array
    {
        return [
            self::cm((string) config('export.page.top', '1.0cm')),
            self::cm((string) config('export.page.right', '2cm')),
            self::cm((string) config('export.page.bottom', '2.5cm')),
            self::cm((string) config('export.page.left', '3cm')),
        ];
    }

    /**
     * Page margins in points: top, right, bottom, left.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function marginsPt(): array
    {
        return array_map(self::cmToPt(...), self::marginsCm());
    }

    /** Printable width in centimetres. */
    public static function contentWidthCm(): float
    {
        [$top, $right, $bottom, $left] = self::marginsCm();

        return round((self::A4_WIDTH_CM) - $left - $right, 2);
    }

    /** Printable width in points, i.e. the page width less both horizontal margins. */
    public static function contentWidthPt(): float
    {
        [, $right, , $left] = self::marginsPt();

        return round(self::A4_WIDTH_PT - $left - $right, 2);
    }

    // ---------------------------------------------------------------------
    // Letterhead
    // ---------------------------------------------------------------------

    public const KOP_FONT = 'Times New Roman';

    public const KOP_FONT_CSS = "'Times New Roman', Times, serif";

    public const BODY_FONT = 'Arial';

    public const BODY_FONT_CSS = 'Arial, Helvetica, sans-serif';

    public const KOP_WIDTH_CM = 18.25;

    public const KOP_WIDTH_TWIPS = 10348;

    public const KOP_BLEED_LEFT_CM = -1.26;

    public const KOP_BLEED_LEFT_TWIPS = -714;

    public const KOP_BLEED_RIGHT_CM = -0.99;

    public const KOP_GAP_BOTTOM_PT = 12.0;

    public const KOP_CELL_GAP_PT = 5.0;

    public const KOP_CELL_PAD_BOTTOM_PT = 4.0;

    public const KOP_LOGO_COL_TWIPS = 2043;

    public const KOP_TEXT_COL_TWIPS = 8305;

    public const KOP_LOGO_WIDTH_PT = 104.0;

    public const KOP_LOGO_HEIGHT_PT = 111.0;

    /**
     * Ministry line: 16 pt, regular weight. It is the largest line but is NOT
     * bold - the implementing agency below it is what carries the weight.
     */
    public const MINISTRY_SIZE_PT = 16.0;

    public const MINISTRY_LINE_PT = 17.5;

    public const AGENCY_SIZE_PT = 14.0;

    public const AGENCY_LINE_PT = 16.0;

    public const ADDRESS_SIZE_PT = 12.0;

    public const ADDRESS_LINE_PT = 13.5;

    /** Letterhead logo fits inside 104 x 111 pt box matching official template. */
    public const LOGO_BOX_PT = 111.0;

    /** Logo column and the text column beside it, as percentages. */
    public const KOP_LOGO_COLUMN = '19.74%';

    public const KOP_TEXT_COLUMN = '80.26%';

    public const RULE_WEIGHT_PT = 2.25;

    // ---------------------------------------------------------------------
    // Body text
    // ---------------------------------------------------------------------

    public const BODY_SIZE_PT = 12.0;

    /** Spasi 1,15 (12 * 1.15 = 13.8 pt), expressed absolutely so both renderers agree. */
    public const BODY_LINE_PT = 13.8;

    public const DOCUMENT_TITLE_SIZE_PT = 14.0;

    /**
     * Clear space and internal gaps, in points.
     *
     * Both the workstation sheet and the exported document put these on the
     * page. They live here so a change of margin does not have to be chased
     * through two templates that were free to drift apart: the numbers used to
     * be typed into each one separately, which is how the preview ended up
     * showing a document that was not the one being exported.
     */
    public const DOC_TITLE_GAP_TOP_PT = 12.0;

    public const DOC_TITLE_GAP_BOTTOM_PT = 6.0;

    public const SECTION_GAP_TOP_PT = 6.0;

    public const SECTION_GAP_BOTTOM_PT = 3.0;

    public const SUBHEADING_GAP_BOTTOM_PT = 1.5;

    public const BLOCK_GAP_BOTTOM_PT = 6.0;

    // ---------------------------------------------------------------------
    // Section titles and the meeting detail table
    // ---------------------------------------------------------------------

    /** The "I. NOTULENSI ..." style headings. */
    public const SECTION_SIZE_PT = 12.0;

    /** The "A. Catatan Jalannya Rapat (Notulensi):" style headings. */
    public const SUBHEADING_SIZE_PT = 12.0;

    /** The Perihal / Hari / Waktu / Format / Penyelenggara table. */
    public const INFO_SIZE_PT = 12.0;

    public const INFO_CELL_PAD_PT = 1.5;

    // ---------------------------------------------------------------------
    // Prose boxes around the minutes and the conclusion
    // ---------------------------------------------------------------------

    public const PROSE_PAD_TOP_PT = 3.0;

    public const PROSE_PAD_SIDE_PT = 5.0;

    // ---------------------------------------------------------------------
    // Detail table (Perihal, Hari/Tanggal, ...)
    // ---------------------------------------------------------------------

    public const DETAIL_LABEL_COLUMN = '28%';

    public const DETAIL_COLON_COLUMN = '2%';

    public const DETAIL_VALUE_COLUMN = '70%';

    // ---------------------------------------------------------------------
    // Attendance table
    // ---------------------------------------------------------------------

    public const ATTENDANCE_SIZE_PT = 8.5;

    public const ATTENDANCE_LINE_PT = 10.5;

    /** Header row fill, matching the PDF's table header. */
    public const ATTENDANCE_HEADER_FILL = 'f2f2f2';

    /** Fill of the prose boxes around the minutes and conclusion. */
    public const PROSE_BOX_FILL = 'fafafa';

    /** Border colour used by the tables and boxes. */
    public const BORDER_COLOR = '000000';

    /**
     * Grid weight of the bordered tables and prose boxes, in points.
     *
     * Both renderers need this: the PDF template writes "1pt solid", the .docx
     * writer has to express the same weight in eighths of a point, which is the
     * unit OOXML uses for w:sz on a border.
     */
    public const BORDER_PT = 1.0;

    /**
     * Column widths, in order: No, NIP, Unit, Waktu, Foto, Tanda Tangan.
     * The name column takes whatever is left over.
     *
     * @return list<string>
     */
    public static function attendanceColumns(): array
    {
        return ['4%', '22%', '11%', '7%', '11%', '15%'];
    }

    public const ATTENDANCE_SELFIE_PX = 26;

    public const ATTENDANCE_SIGNATURE_WIDTH_PX = 75;

    public const ATTENDANCE_SIGNATURE_HEIGHT_PX = 22;

    // ---------------------------------------------------------------------
    // Signature block
    // ---------------------------------------------------------------------

    /** Inner block width inside a signature column, as a percentage. */
    public const TTD_INNER_WIDTH_TWO_COLUMN = '68%';

    public const TTD_INNER_WIDTH_THREE_COLUMN = '98%';

    public const TTD_SIGNATURE_WIDTH_PX = 95;

    public const TTD_SIGNATURE_HEIGHT_PX = 32;

    public const TTD_ROW_HEIGHT_PT = 36;

    /** Clear space between the attendance table and the signature block. */
    public const TTD_GAP_PT = 18.0;

    // ---------------------------------------------------------------------
    // Documentation photos
    // ---------------------------------------------------------------------

    public const PHOTO_WIDTH_PX = 220;

    public const PHOTO_HEIGHT_PX = 135;

    /** Frame around each annex photo, matching the PDF's 1 px slate outline. */
    public const PHOTO_FRAME_COLOR = '94a3b8';

    public const PHOTO_CAPTION_SIZE_PT = 7.5;

    // ---------------------------------------------------------------------
    // Document footer note
    // ---------------------------------------------------------------------

    /** The office note printed under the signature block. */
    public const FOOTER_NOTE_SIZE_PT = 7.5;

    public const FOOTER_NOTE_COLOR = '64748b';

    /** Hairline that separates the note from the signature block above it. */
    public const FOOTER_NOTE_RULE_PT = 0.75;

    public const FOOTER_NOTE_RULE_COLOR = 'cbd5e1';

    /** Clear space above the rule, and between the rule and the note. */
    public const FOOTER_NOTE_GAP_PT = 8.0;

    public const FOOTER_NOTE_PAD_TOP_PT = 3.0;

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Read a CSS length such as "1.5cm" and return just the number.
     */
    public static function cm(string $length): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $length);
    }

    /**
     * Convert centimetres to points: 1 cm = 28.3465 pt.
     */
    public static function cmToPt(float $cm): float
    {
        return round($cm * 28.3465, 2);
    }

    /**
     * Convert centimetres to twips, the unit .docx measures table widths in.
     *
     * PhpWord 0.18 has no unit parameter on setWidth() or addCell(): the
     * number is written straight out as dxa. Passing centimetres therefore
     * produced a 3.23 twip column, which is to say no column at all.
     */
    public static function cmToTwips(float $cm): int
    {
        return (int) round($cm * 566.929);
    }

    /**
     * Convert points to twips, the unit .docx stores measurements in.
     */
    public static function ptToTwips(float $pt): int
    {
        return (int) round($pt * 20);
    }

    /**
     * Convert points to the hundredths of a point that OOXML font sizes use.
     */
    public static function ptToHalfPoints(float $pt): int
    {
        return (int) round($pt * 2);
    }

    /**
     * Convert points to eighths of a point, the unit w:sz uses on a border.
     *
     * Feeding w:sz a twip count instead draws a border ten times too heavy:
     * 1 pt is 20 twips but only 8 eighths.
     */
    public static function ptToBorderEighths(float $pt): int
    {
        return (int) round($pt * 8);
    }

    /**
     * Convert pixels to points, for the HTML renderer's px units.
     */
    public static function pxToPt(float $px): float
    {
        return round($px * 0.75, 2);
    }
}
