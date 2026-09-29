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

    /**
     * Page margins in centimetres: top, right, bottom, left.
     *
     * Left is deliberately wider: the letterhead guidance reserves that side for
     * binding. The top margin also keeps the letterhead clear of the unprintable
     * band of office printers.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function marginsCm(): array
    {
        return [
            self::cm((string) config('export.page.top', '1.5cm')),
            self::cm((string) config('export.page.right', '2cm')),
            self::cm((string) config('export.page.bottom', '1.5cm')),
            self::cm((string) config('export.page.left', '2cm')),
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

    /**
     * Ministry line: 16 pt, regular weight. It is the largest line but is NOT
     * bold - the implementing agency below it is what carries the weight.
     */
    public const MINISTRY_SIZE_PT = 16.0;

    public const MINISTRY_LINE_PT = 19.0;

    public const AGENCY_SIZE_PT = 14.0;

    public const AGENCY_LINE_PT = 17.0;

    public const ADDRESS_SIZE_PT = 12.0;

    public const ADDRESS_LINE_PT = 14.0;

    /** Letterhead logo fits inside a 28 mm box. */
    public const LOGO_BOX_PT = 79.4;

    /** Logo column and the text column beside it, as percentages. */
    public const KOP_LOGO_COLUMN = '19%';

    public const KOP_TEXT_COLUMN = '81%';

    public const RULE_WEIGHT_PT = 1.0;

    // ---------------------------------------------------------------------
    // Body text
    // ---------------------------------------------------------------------

    public const BODY_SIZE_PT = 12.0;

    /** Spasi 1,5, expressed absolutely so both renderers agree. */
    public const BODY_LINE_PT = 18.0;

    public const DOCUMENT_TITLE_SIZE_PT = 14.0;

    // ---------------------------------------------------------------------
    // Detail table (Perihal, Hari/Tanggal, ...)
    // ---------------------------------------------------------------------

    public const DETAIL_LABEL_COLUMN = '24%';

    public const DETAIL_COLON_COLUMN = '2%';

    public const DETAIL_VALUE_COLUMN = '74%';

    // ---------------------------------------------------------------------
    // Attendance table
    // ---------------------------------------------------------------------

    public const ATTENDANCE_SIZE_PT = 9.0;

    public const ATTENDANCE_LINE_PT = 11.0;

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
        return ['4%', '15%', '12%', '9%', '15%', '15%'];
    }

    public const ATTENDANCE_SELFIE_PX = 26;

    public const ATTENDANCE_SIGNATURE_WIDTH_PX = 75;

    public const ATTENDANCE_SIGNATURE_HEIGHT_PX = 22;

    // ---------------------------------------------------------------------
    // Signature block
    // ---------------------------------------------------------------------

    /** Inner block width inside a signature column, as a percentage. */
    public const TTD_INNER_WIDTH_TWO_COLUMN = '68%';

    public const TTD_INNER_WIDTH_THREE_COLUMN = '76%';

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
