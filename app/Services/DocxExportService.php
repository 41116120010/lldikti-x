<?php

namespace App\Services;

use App\Models\Agenda;
use App\Support\DocumentLayout;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\LineSpacingRule;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use RuntimeException;

/**
 * Builds the Berita Acara as a real .docx.
 *
 * The old Word export served an HTML page as application/ms-word, so Word had to
 * guess the document through its own HTML importer while the PDF went through
 * Dompdf. Two unrelated engines meant any construct only one of them understood
 * was silently dropped by the other - margins, column widths and images drifted
 * apart while both files kept looking like documents.
 *
 * Every measurement here comes from DocumentLayout, the same values the PDF
 * template reads, so the two documents cannot drift.
 *
 * Two behaviours of PhpWord were established by measuring a rendered file rather
 * than by reading the source, and both are load-bearing:
 *
 *  - Text is written VERBATIM. A label such as "Format & Tempat" lands in
 *    word/document.xml as a bare ampersand, which makes that part unparseable
 *    and the whole document unopenable. plain() therefore escapes everything.
 *  - Image width and height are POINTS. Passing EMU renders the image 12700
 *    times too large, which is what pushed each attendance row onto its own
 *    page.
 */
class DocxExportService
{
    private const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    /** Images spilled to disk for embedding, removed once the package is written. */
    private array $temporaryFiles = [];

    public function __construct(
        protected WordExportService $wordExportService,
    ) {}

    /**
     * @param  array<string,mixed>  $config  Report-config overrides for this single export.
     */
    public function exportBeritaAcara(Agenda $agenda, array $config = []): Response
    {
        $data = $this->wordExportService->prepareViewData($agenda, $config);

        try {
            $bytes = $this->build($data);
        } finally {
            $this->cleanTemporaryFiles();
        }

        $filename = 'Berita_Acara_'.$agenda->slug.'.docx';

        Log::info('DOCX export selesai', [
            'agenda_id' => $agenda->id,
            'bytes' => strlen($bytes),
        ]);

        return response($bytes, 200, [
            'Content-Type' => self::CONTENT_TYPE,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function build(array $data): string
    {
        $document = new PhpWord;
        $section = $document->addSection();

        [$top, $right, $bottom, $left] = array_map(
            DocumentLayout::cm(...),
            DocumentLayout::marginsCm()
        );

        $section->setPageSize('A4');
        // Section::setMargins() is a no-op in this version: the rendered page
        // kept Word's 1 inch default on all four sides. The style array is the
        // route that actually reaches the writer, and it takes twips.
        $section->setStyle([
            'pageSizeW' => DocumentLayout::ptToTwips(DocumentLayout::A4_WIDTH_PT),
            'pageSizeH' => DocumentLayout::ptToTwips(DocumentLayout::A4_HEIGHT_PT),
            'marginTop' => DocumentLayout::cmToTwips($top),
            'marginRight' => DocumentLayout::cmToTwips($right),
            'marginBottom' => DocumentLayout::cmToTwips($bottom),
            'marginLeft' => DocumentLayout::cmToTwips($left),
        ]);

        $this->addLetterhead($section, $data);
        $this->addTitle($section, $data);
        $this->addDetailTable($section, $data);
        $this->addMinutes($section, $data);
        $this->addAttendance($section, $data);
        $this->addSignatures($section, $data);
        $this->addFooterNote($section, $data);
        $this->addPhotoAnnex($section, $data);

        $file = tempnam(sys_get_temp_dir(), 'siperapat_docx_');
        $this->temporaryFiles[] = $file;

        IOFactory::createWriter($document, 'Word2007')->save($file);

        $bytes = (string) file_get_contents($file);

        if ($bytes === '') {
            throw new RuntimeException('Dokumen DOCX yang dihasilkan kosong.');
        }

        return $bytes;
    }

    // -----------------------------------------------------------------
    // Letterhead
    // -----------------------------------------------------------------

    /**
     * @param  array<string,mixed>  $data
     */
    private function addLetterhead(mixed $section, array $data): void
    {
        $config = $data['config'];

        if (! ($config['show_kop'] ?? true)) {
            return;
        }

        $contentCm = DocumentLayout::contentWidthCm();
        $logoWidth = $this->logoWidthPt($data);
        $showLogo = ($config['show_logo'] ?? true) && $logoWidth !== null;

        $leftCm = $showLogo ? $contentCm * 0.19 : 0.0;
        $rightCm = $contentCm - $leftCm;

        $table = $section->addTable($this->tableStyle());
        $row = $table->addRow(null, $this->rowStyle());

        $logoCell = $row->addCell(DocumentLayout::cmToTwips($leftCm), ['vAlign' => 'center']);

        if ($showLogo && $logoWidth !== null) {
            $this->embedImage(
                $logoCell,
                $data['logoBase64'] ?? null,
                'png',
                $logoWidth,
                DocumentLayout::LOGO_BOX_PT
            );
        }

        $textCell = $row->addCell(DocumentLayout::cmToTwips($rightCm), ['vAlign' => 'center']);

        $textCell->addText($this->plain($config['instansi_induk'] ?? ''), ...$this->style(
            DocumentLayout::MINISTRY_SIZE_PT, false, DocumentLayout::MINISTRY_LINE_PT, 'center'
        ));
        $textCell->addText($this->plain($config['instansi_pelaksana'] ?? ''), ...$this->style(
            DocumentLayout::AGENCY_SIZE_PT, true, DocumentLayout::AGENCY_LINE_PT, 'center'
        ));
        $textCell->addText($this->plain($config['alamat_kontak'] ?? ''), ...$this->style(
            DocumentLayout::ADDRESS_SIZE_PT, false, DocumentLayout::ADDRESS_LINE_PT, 'center', false, 4
        ));

        // The closing rule of the letterhead.
        //
        // A bare table cannot draw a line under itself, so the rule is a second
        // row of one merged cell whose only property is a bottom border. The
        // paragraph inside carries no text and one point of type: an empty
        // paragraph in Word still reserves a full line box, and a taller one
        // would push the document title away from the rule.
        $ruleRow = $table->addRow(null, $this->rowStyle());
        $ruleRow->addCell(DocumentLayout::cmToTwips($contentCm), [
            'gridSpan' => $showLogo ? 2 : 1,
            'borderBottomSize' => DocumentLayout::ptToBorderEighths(DocumentLayout::RULE_WEIGHT_PT),
            'borderBottomColor' => DocumentLayout::BORDER_COLOR,
        ])->addText('', ...$this->style(1, false, null, 'left', false, 0, null));
    }

    // -----------------------------------------------------------------
    // Title and detail table
    // -----------------------------------------------------------------

    /**
     * @param  array<string,mixed>  $data
     */
    private function addTitle(mixed $section, array $data): void
    {
        $config = $data['config'];
        $agenda = $data['agenda'];

        $section->addText($this->plain($config['document_title'] ?? ''), ...$this->style(DocumentLayout::DOCUMENT_TITLE_SIZE_PT, true, null, 'center', underline: true, spaceBeforePt: 10)
        );

        if ($config['show_document_number'] ?? true) {
            $number = $config['document_number']
                ?? ('BA-RAPAT/'.date('Y').'/'.str_pad($agenda->id, 4, '0', STR_PAD_LEFT));

            $section->addText('Nomor: '.$this->plain($number), ...$this->style(DocumentLayout::BODY_SIZE_PT, false, null, 'center'));
        }
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function addDetailTable(mixed $section, array $data): void
    {
        $config = $data['config'];
        $agenda = $data['agenda'];

        if (! ($config['show_meeting_info'] ?? true)) {
            return;
        }

        $contentCm = DocumentLayout::contentWidthCm();
        $labelCm = $contentCm * 0.24;
        $colonCm = $contentCm * 0.02;
        $valueCm = $contentCm - $labelCm - $colonCm;

        $rows = [
            ['Perihal / Agenda', $config['custom_agenda_title'] ?? $agenda->judul_rapat],
            ['Hari / Tanggal', $agenda->waktu_mulai->translatedFormat('l, d F Y')],
            ['Waktu Pelaksanaan', $agenda->waktu_mulai->format('H:i').' '
                .($agenda->waktu_selesai ? 's.d. '.$agenda->waktu_selesai->format('H:i').' WIB' : 'WIB s.d. Selesai')],
            ['Format & Tempat', ucfirst($agenda->tipe_rapat).' - '
                .($config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)'))],
            ['Penyelenggara Rapat', ($agenda->creator?->name ?? '-').' ('
                .($agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga').')'],
        ];

        $table = $section->addTable($this->tableStyle());
        foreach ($rows as [$caption, $text]) {
            $row = $table->addRow(null, $this->rowStyle());

            $row->addCell(DocumentLayout::cmToTwips($labelCm))->addText($this->plain($caption), ...$this->style(DocumentLayout::BODY_SIZE_PT, true, null, null, false, 8));
            $row->addCell(DocumentLayout::cmToTwips($colonCm))->addText(':', ...$this->style(DocumentLayout::BODY_SIZE_PT));
            $row->addCell(DocumentLayout::cmToTwips($valueCm))->addText($this->plain((string) $text), ...$this->style(DocumentLayout::BODY_SIZE_PT));
        }
    }

    // -----------------------------------------------------------------
    // Minutes
    // -----------------------------------------------------------------

    /**
     * @param  array<string,mixed>  $data
     */
    private function addMinutes(mixed $section, array $data): void
    {
        $config = $data['config'];
        $agenda = $data['agenda'];

        $notes = $config['show_notulensi'] ?? true;
        $conclusion = $config['show_kesimpulan'] ?? true;

        if (! $notes && ! $conclusion) {
            return;
        }

        $section->addText($this->plain('I. NOTULENSI & KESIMPULAN RAPAT'), ...$this->style(DocumentLayout::BODY_SIZE_PT, true, null, null, false, 8));

        if ($notes) {
            $section->addText($this->plain('A. Catatan Jalannya Rapat (Notulensi):'), ...$this->style(DocumentLayout::BODY_SIZE_PT, true, null, null, false, 8));
            $this->boxedProse($section, $this->plain($agenda->formatted_notulensi ?: ''));
        }

        if ($conclusion) {
            $section->addText($this->plain('B. Kesimpulan & Rencana Tindak Lanjut (RTL):'), ...$this->style(DocumentLayout::BODY_SIZE_PT, true, null, null, false, 8));
            $this->boxedProse($section, $this->plain($agenda->formatted_kesimpulan ?: ''));
        }
    }

    // -----------------------------------------------------------------
    // Attendance
    // -----------------------------------------------------------------

    /**
     * @param  array<string,mixed>  $data
     */
    private function addAttendance(mixed $section, array $data): void
    {
        $config = $data['config'];
        $attendances = $data['attendances'];

        if (! ($config['show_attendees'] ?? true)) {
            return;
        }

        // The preceding block is a table in every configuration: the closing
        // prose box when either minutes section is on, the meeting detail table
        // when both are off. See spacer().
        $this->spacer($section);

        $section->addText($this->plain('II. DAFTAR KEHADIRAN PESERTA ('.$attendances->count().' Orang)'), ...$this->style(DocumentLayout::BODY_SIZE_PT, true)
        );

        $contentCm = DocumentLayout::contentWidthCm();
        $widths = $this->attendanceWidths($config, $contentCm);

        // Ruled: the PDF draws a 1 pt black grid on this table only.
        $table = $section->addTable($this->tableStyle(true));
        $header = $table->addRow(null, $this->rowStyle(true));

        $this->attendanceHeadCell($header, $widths, 'no', 'No', 'center');
        $this->attendanceHeadCell($header, $widths, 'name', 'Nama Lengkap', 'left');
        if ($config['show_nip'] ?? true) {
            $this->attendanceHeadCell($header, $widths, 'nip', 'NIP', 'left');
        }
        if ($config['show_unit'] ?? true) {
            $this->attendanceHeadCell($header, $widths, 'unit', 'Unit Kerja / Pokja', 'left');
        }
        if ($config['show_attendance_time'] ?? true) {
            $this->attendanceHeadCell($header, $widths, 'time', 'Waktu', 'center');
        }
        if ($config['show_selfie_photos'] ?? true) {
            $this->attendanceHeadCell($header, $widths, 'photo', 'Foto Kehadiran', 'center');
        }
        $this->attendanceHeadCell($header, $widths, 'sign', 'Tanda Tangan', 'center');

        foreach ($attendances as $index => $entry) {
            $user = $entry['model']->user;
            $row = $table->addRow(null, $this->rowStyle());

            $this->attendanceBodyCell($row, $widths, 'no', (string) ($index + 1), 'center');
            $this->attendanceBodyCell($row, $widths, 'name', $this->plain($user->name), 'left', true);

            if ($config['show_nip'] ?? true) {
                $this->attendanceBodyCell($row, $widths, 'nip', $this->plain($user->nip), 'left');
            }
            if ($config['show_unit'] ?? true) {
                $this->attendanceBodyCell($row, $widths, 'unit', $this->plain($user->unit?->kode_unit ?? 'Pusat'), 'left');
            }
            if ($config['show_attendance_time'] ?? true) {
                $this->attendanceBodyCell($row, $widths, 'time', $entry['model']->signed_at?->format('H:i') ?? '-', 'center');
            }
            if ($config['show_selfie_photos'] ?? true) {
                $selfie = DocumentLayout::ATTENDANCE_SELFIE_PX;

                $this->embedImage(
                    $row->addCell(DocumentLayout::cmToTwips($widths['photo']), ['vAlign' => 'center']),
                    $entry['selfie_base64'] ?? null,
                    'jpeg',
                    DocumentLayout::pxToPt($selfie),
                    DocumentLayout::pxToPt($selfie)
                );
            }

            $this->attendanceSignature($row, $widths['sign'], $config, $entry['sig_base64'] ?? null);
        }

        if ($attendances->isEmpty()) {
            $row = $table->addRow(null, $this->rowStyle());

            $row->addCell(DocumentLayout::cmToTwips($contentCm), ['gridSpan' => count($widths) - 2])
                ->addText(
                    $this->plain('Tidak ada data kehadiran peserta yang tercatat.'),
                    ...$this->style(DocumentLayout::ATTENDANCE_SIZE_PT, false, null, 'center')
                );
        }
    }

    /**
     * One header cell of the attendance table.
     *
     * The centre and left split is copied from the PDF template and is not
     * cosmetic: it is what keeps the 4 % "No" and 9 % "Waktu" columns from
     * reading as ragged left text, and stops the 30 % name and NIP columns
     * looking stranded in the middle of a wide cell.
     *
     * @param  array<string,float>  $widths
     */
    private function attendanceHeadCell(mixed $row, array $widths, string $key, string $caption, string $align): void
    {
        $row->addCell(
            DocumentLayout::cmToTwips($widths[$key]),
            ['shading' => ['fill' => DocumentLayout::ATTENDANCE_HEADER_FILL]]
        )->addText($caption, ...$this->style(DocumentLayout::ATTENDANCE_SIZE_PT, true, null, $align));
    }

    /**
     * One body cell of the attendance table.
     *
     * @param  array<string,float>  $widths
     */
    private function attendanceBodyCell(mixed $row, array $widths, string $key, string $text, string $align, bool $bold = false): void
    {
        $row->addCell(DocumentLayout::cmToTwips($widths[$key]), ['vAlign' => 'center'])
            ->addText($text, ...$this->style(DocumentLayout::ATTENDANCE_SIZE_PT, $bold, null, $align));
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array<string,float>
     */
    private function attendanceWidths(array $config, float $contentCm): array
    {
        $columns = DocumentLayout::attendanceColumns();
        $share = static fn (string $w): float => $contentCm * ((float) rtrim($w, '%')) / 100;

        $widths = [
            'no' => $share($columns[0]),
            'nip' => $share($columns[1]),
            'unit' => $share($columns[2]),
            'time' => $share($columns[3]),
            'photo' => $share($columns[4]),
            'sign' => $share($columns[5]),
        ];

        $used = $widths['no'] + $widths['sign'];
        foreach (['nip' => 'show_nip', 'unit' => 'show_unit', 'time' => 'show_attendance_time', 'photo' => 'show_selfie_photos'] as $key => $option) {
            if ($config[$option] ?? true) {
                $used += $widths[$key];
            }
        }

        $widths['name'] = max(0.0, $contentCm - $used);

        return $widths;
    }

    /**
     * The Tanda Tangan cell of an attendance row.
     *
     * The PDF writes "(HADIR)" in green whenever the signature is switched off
     * or the participant never signed. The .docx used to embed whatever base64 it
     * was handed, so a row whose signature the officer chose not to draw came out
     * as an empty bordered cell: a silent gap in a document that is meant to be
     * the proof of attendance.
     *
     * @param  array<string,mixed>  $config
     */
    private function attendanceSignature(mixed $row, float $widthCm, array $config, ?string $sigBase64): void
    {
        $cell = $row->addCell(DocumentLayout::cmToTwips($widthCm), ['vAlign' => 'center']);

        $show = ($config['show_attendee_signatures'] ?? true)
            && is_string($sigBase64)
            && str_contains($sigBase64, 'base64,');

        if ($show) {
            $this->embedImage(
                $cell,
                $sigBase64,
                'png',
                DocumentLayout::pxToPt(DocumentLayout::ATTENDANCE_SIGNATURE_WIDTH_PX),
                DocumentLayout::pxToPt(DocumentLayout::ATTENDANCE_SIGNATURE_HEIGHT_PX)
            );

            return;
        }

        $cell->addText(
            '(HADIR)',
            ...$this->style(DocumentLayout::ATTENDANCE_SIZE_PT - 1.5, true, null, 'center')
        );
    }

    // -----------------------------------------------------------------
    // Signatures
    // -----------------------------------------------------------------

    /**
     * @param  array<string,mixed>  $data
     */
    private function addSignatures(mixed $section, array $data): void
    {
        $config = $data['config'];
        $agenda = $data['agenda'];

        // Always follows a table, and the PDF's own 18 pt gap above the block
        // belongs here, on the separator, not on the first line of the block.
        $this->spacer($section, DocumentLayout::TTD_GAP_PT);

        $signers = [[
            'label' => 'Mengetahui,',
            'role' => $config['signer1_role'] ?? 'Pemimpin Rapat',
            'name' => $config['signer1_name'] ?? $agenda->nama_pimpinan,
            'nip' => $config['signer1_nip'] ?? $agenda->nip_pimpinan,
            // The officer can switch a mark off on the workstation page. Reading
            // the base64 straight into the document ignored that switch, so the
            // PDF dropped the mark and the .docx printed it anyway - two copies
            // of one signed record disagreeing about what had been signed.
            // Absent data and a switched-off mark mean the same thing here: no
            // image, which leaves the cell empty exactly as the PDF does.
            'image' => ($config['show_signer1_signature'] ?? true)
                ? ($data['pimpinanSigBase64'] ?? null)
                : null,
        ]];

        if ($config['show_signer3'] ?? false) {
            $signers[] = [
                'label' => 'Menyetujui,',
                'role' => $config['signer3_role'] ?? 'Kepala LLDIKTI',
                'name' => $config['signer3_name'] ?? '-',
                'nip' => $config['signer3_nip'] ?? '-',
                'image' => null,
            ];
        }

        $signers[] = [
            'label' => ($config['signing_city'] ?? 'Padang').', '
                .($config['signing_date'] ?? now()->translatedFormat('d F Y')),
            'role' => $config['signer2_role'] ?? 'Notulis Rapat',
            'name' => $config['signer2_name'] ?? $agenda->nama_notulis,
            'nip' => $config['signer2_nip'] ?? $agenda->nip_notulis,
            'image' => ($config['show_signer2_signature'] ?? true)
                ? ($data['notulisSigBase64'] ?? null)
                : null,
        ];

        $contentCm = DocumentLayout::contentWidthCm();
        $count = count($signers);
        $columnCm = $contentCm / $count;
        $columnTwips = DocumentLayout::cmToTwips($columnCm);

        // The PDF centres a narrow block inside each column and lets the text sit
        // against that block's left edge, so the block is 68 % of the column with
        // two signers and 76 % with three. Word has no inline-block, but a
        // symmetric paragraph indent reproduces the same geometry exactly: the
        // text starts where the PDF starts it and wraps where the PDF wraps it.
        $innerPercent = $count >= 3
            ? DocumentLayout::TTD_INNER_WIDTH_THREE_COLUMN
            : DocumentLayout::TTD_INNER_WIDTH_TWO_COLUMN;
        $indentTwips = (int) round($columnTwips * (100 - (float) rtrim($innerPercent, '%')) / 200);

        $table = $section->addTable($this->tableStyle());

        // THREE rows, one cell per signer per row. Building a row per signer, as
        // this used to, stacks the three signers into a single column of nine
        // lines: each signer got its own label row, image row and name row, and
        // each of those rows held exactly one cell of the full column width.
        $caption = $table->addRow(null, $this->rowStyle());
        $marks = $table->addRow(DocumentLayout::ptToTwips(DocumentLayout::TTD_ROW_HEIGHT_PT), $this->rowStyle());
        $names = $table->addRow(null, $this->rowStyle());

        foreach ($signers as $signer) {
            $captionCell = $caption->addCell($columnTwips, ['vAlign' => 'top']);
            $captionCell->addText($this->plain($signer['label']), ...$this->style(
                DocumentLayout::BODY_SIZE_PT, false, DocumentLayout::BODY_LINE_PT, 'left', false, 0, $indentTwips
            ));
            $captionCell->addText($this->plain($signer['role']), ...$this->style(
                DocumentLayout::BODY_SIZE_PT, true, DocumentLayout::BODY_LINE_PT, 'left', false, 0, $indentTwips
            ));

            // Centred, not bottom aligned. A signature is a mark on a line: with
            // bottom alignment two squiggles of different heights sit at different
            // heights, which reads as one signer having signed further down. The
            // PDF centres the cell, so the centres are what must line up.
            $markCell = $marks->addCell($columnTwips, ['vAlign' => 'center']);
            $this->embedImage(
                $markCell,
                $signer['image'],
                'png',
                DocumentLayout::pxToPt(DocumentLayout::TTD_SIGNATURE_WIDTH_PX),
                DocumentLayout::pxToPt(DocumentLayout::TTD_SIGNATURE_HEIGHT_PX),
                'left'
            );

            $nameCell = $names->addCell($columnTwips, ['vAlign' => 'top']);
            $nameCell->addText($this->plain($signer['name']), ...$this->style(
                DocumentLayout::BODY_SIZE_PT, true, DocumentLayout::BODY_LINE_PT, 'left', false, 4, $indentTwips
            ));
            $nameCell->addText($this->plain('NIP '.$signer['nip']), ...$this->style(
                DocumentLayout::BODY_SIZE_PT, false, DocumentLayout::BODY_LINE_PT, 'left', false, 0, $indentTwips
            ));
        }
    }

    // -----------------------------------------------------------------
    // Document footer note
    // -----------------------------------------------------------------

    /**
     * The office note printed under the signature block.
     *
     * The toggle and the text behind it were editable on the workstation page
     * and stored with the agenda, but neither renderer read them: the note was
     * visible while it was being typed, survived a save, and then vanished from
     * the exported document. An option that silently does nothing is worse than
     * one that is missing, because the preview confirms it before it is lost.
     *
     * The separator rule is a one cell table because Style\Paragraph in this
     * version of PhpWord has no border at all, and a paragraph is the only
     * other way to draw a line. The table is a single column spanning the full
     * measure, so it is visually one rule and one centred line of text.
     *
     * @param  array<string,mixed>  $data
     */
    private function addFooterNote(mixed $section, array $data): void
    {
        $config = $data['config'];
        $text = $this->plain($config['footer_note'] ?? '');

        // Tanpa catatan yang ditulis petugas, blok dilewati seluruhnya.
        // Menyisakan garis pemisah tanpa teks di bawahnya lebih buruk daripada
        // tidak ada blok sama sekali.
        if (! ($config['show_footer_note'] ?? false) || $text === '') {
            return;
        }

        $this->spacer($section, DocumentLayout::FOOTER_NOTE_GAP_PT);

        $table = $section->addTable(array_merge($this->tableStyle(), [
            'cellMarginTop' => DocumentLayout::ptToTwips(DocumentLayout::FOOTER_NOTE_PAD_TOP_PT),
        ]));

        $table->addRow(null, $this->rowStyle())->addCell(
            DocumentLayout::cmToTwips(DocumentLayout::contentWidthCm()),
            [
                'borderTopSize' => DocumentLayout::ptToBorderEighths(DocumentLayout::FOOTER_NOTE_RULE_PT),
                'borderTopColor' => DocumentLayout::FOOTER_NOTE_RULE_COLOR,
            ]
        )->addText($text, ...$this->style(
            DocumentLayout::FOOTER_NOTE_SIZE_PT, false, null, 'center'
        ));
    }

    // -----------------------------------------------------------------
    // Photo annex
    // -----------------------------------------------------------------

    /**
     * @param  array<string,mixed>  $data
     */
    private function addPhotoAnnex(mixed $section, array $data): void
    {
        $config = $data['config'];
        $documentations = $data['documentations'];

        if (! ($config['show_documentation'] ?? true) || $documentations->isEmpty()) {
            return;
        }

        // Supporting material, not part of the minutes: always opens a new page
        // so the closing page ends cleanly on the signature block.
        //
        // The break is set on the heading itself rather than with addPageBreak().
        // addPageBreak() creates its own paragraph, and when the preceding page
        // happens to be full that paragraph lands on a page of its own, leaving
        // a blank page between the minutes and the annex.
        $heading = $this->style(DocumentLayout::BODY_SIZE_PT, true, null, 'center');
        $heading[1]['pageBreakBefore'] = true;
        $section->addText($this->plain('III. LAMPIRAN FOTO DOKUMENTASI KEGIATAN'), ...$heading);

        $contentCm = DocumentLayout::contentWidthCm();
        $columnTwips = DocumentLayout::cmToTwips($contentCm / 2);

        // The PDF frames each photo in a light slate box with the caption
        // underneath in italic. Without the border and the caption the annex read
        // as a loose grid of bare images, which is not what the officer signs off
        // on. The frame lives on the cell rather than on the table: a table border
        // would also draw the divider between the two columns, which the PDF does
        // not have. A cell with no photo in it is left plain, for the same reason
        // the PDF leaves its placeholder cell borderless.
        $frame = [
            'vAlign' => 'top',
            'borderSize' => DocumentLayout::ptToBorderEighths(DocumentLayout::BORDER_PT),
            'borderColor' => DocumentLayout::PHOTO_FRAME_COLOR,
        ];

        foreach ($documentations->chunk(2) as $chunk) {
            $table = $section->addTable($this->tableStyle());
            $row = $table->addRow(null, $this->rowStyle());

            foreach ([0, 1] as $position) {
                if (! isset($chunk[$position])) {
                    $row->addCell($columnTwips, ['vAlign' => 'top']);

                    continue;
                }

                $cell = $row->addCell($columnTwips, $frame);

                $this->embedImage(
                    $cell,
                    $chunk[$position]['base64'] ?? null,
                    'jpeg',
                    DocumentLayout::pxToPt(DocumentLayout::PHOTO_WIDTH_PX),
                    DocumentLayout::pxToPt(DocumentLayout::PHOTO_HEIGHT_PX)
                );

                $caption = $chunk[$position]['model']->caption ?? null;

                if (is_string($caption) && trim($caption) !== '') {
                    $cell->addText(
                        $this->plain($caption),
                        ...$this->style(DocumentLayout::PHOTO_CAPTION_SIZE_PT, false, null, 'center', false, 4, null, true)
                    );
                }
            }
        }
    }

    // -----------------------------------------------------------------
    // Styles and helpers
    // -----------------------------------------------------------------

    /**
     * A run style. Line height, when given, is exact so the paragraph grid holds
     * between Word and the PDF.
     *
     * @return array<string,mixed>
     */
    /**
     * Run and paragraph styles as two separate arrays.
     *
     * addText() takes the run style second and the paragraph style third, and
     * an array given as the run style is applied to Style\Font only. Alignment,
     * spacing and line height therefore have to travel in the third argument;
     * merged into the run style they are discarded without a word.
     *
     * @return array{0: array<string,mixed>, 1: array<string,mixed>}
     */
    private function style(
        float $sizePt,
        bool $bold = false,
        ?float $linePt = null,
        ?string $align = null,
        bool $underline = false,
        float $spaceBeforePt = 0,
        ?int $indentTwips = null,
        bool $italic = false,
    ): array {
        $run = [
            'name' => 'Times New Roman',
            'size' => $sizePt,
            'bold' => $bold,
        ];

        if ($underline) {
            $run['underline'] = 'single';
        }

        if ($italic) {
            $run['italic'] = true;
        }

        $paragraph = [
            'spaceBefore' => DocumentLayout::ptToTwips($spaceBeforePt),
            'spaceAfter' => 0,
        ];

        if ($linePt !== null) {
            $paragraph['spacing'] = [
                'before' => DocumentLayout::ptToTwips($spaceBeforePt),
                'after' => 0,
                'line' => DocumentLayout::ptToTwips($linePt),
                'lineRule' => LineSpacingRule::EXACT,
            ];
        }

        if ($align !== null) {
            $paragraph['align'] = $align;
        }

        if ($indentTwips !== null) {
            // Symmetric on purpose. The PDF centres a narrower block inside the
            // cell and lets the text sit against that block's left edge, so the
            // same offset has to be reserved on the right as well or the line
            // would wrap wider than the block it belongs to.
            $paragraph['indentation'] = ['left' => $indentTwips, 'right' => $indentTwips];
        }

        return [$run, $paragraph];
    }

    /**
     * A paragraph of prose inside a bordered, lightly filled box.
     *
     * The PDF wraps the minutes in a one cell table purely for its frame. Word
     * needs the same construct, or the block loses its outline entirely.
     */
    private function boxedProse(mixed $section, string $text): void
    {
        $table = $section->addTable(array_merge($this->tableStyle(), [
            'borderSize' => DocumentLayout::ptToBorderEighths(DocumentLayout::BORDER_PT),
            'borderColor' => DocumentLayout::BORDER_COLOR,
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 100,
            'cellMarginRight' => 100,
        ]));

        $row = $table->addRow(null, $this->rowStyle());
        $cell = $row->addCell(DocumentLayout::ptToTwips(DocumentLayout::contentWidthPt()), [
            'shading' => ['fill' => DocumentLayout::PROSE_BOX_FILL],
        ]);
        $cell->addText($text, ...$this->style(
            DocumentLayout::BODY_SIZE_PT, false, DocumentLayout::BODY_LINE_PT, 'both'
        ));
    }

    /**
     * Put an image in a cell at an explicit size, centred on the cell's axis.
     *
     * Sizes are POINTS, which is the unit the .docx writer stores an extent in.
     * Callers holding a pixel measurement from DocumentLayout convert it at the
     * call site with pxToPt(), so the conversion is visible where the number
     * comes from rather than hidden inside a helper.
     *
     * Centring is not a default for its own sake. addImage() opens its own
     * paragraph and that paragraph inherits the document default, so the picture
     * lands hard against the left edge of the cell, while the PDF template centres
     * every one of these - the letterhead logo with "margin: 0 auto", the
     * attendance selfie inside a centred cell, the annex photo likewise. The only
     * image the PDF leaves left aligned is the signature inside the signature
     * block, whose inner box is text-align: left, and that one says so.
     *
     * The image is written into a paragraph this method creates rather than into
     * the one addImage() would open, because the alignment has to travel with the
     * picture and there is no way to restyle that paragraph afterwards. A TextRun
     * is the paragraph inside a cell, and it is a container in its own right, so
     * the picture is added to it and not to the cell.
     */
    private function embedImage(mixed $cell, ?string $dataUri, string $extension, float $wPt, float $hPt, string $align = 'center'): void
    {
        $path = $this->spill($dataUri, $extension);

        if ($path === null) {
            return;
        }

        $cell->addTextRun($this->style(1, false, null, $align)[1])->addImage($path, [
            'width' => $wPt,
            'height' => $hPt,
        ]);
    }

    /**
     * Reduce rich text to plain text and XML-escape it.
     *
     * The escaping is not optional. PhpWord writes run text verbatim in both its
     * 0.18 and 1.x lines, so a label such as "Format & Tempat" would land in
     * word/document.xml as a bare ampersand, making that part unparseable and
     * the whole document unopenable. Tags become a space rather than nothing,
     * so words either side of a </p><p> boundary do not splice together.
     */
    /**
     * Apply a table's own style container.
     *
     * Word injects a default cell margin of 0.19 cm on the left and right of
     * every cell. On a seven column attendance table that padding is what
     * squeezed the NIP column until the digits wrapped and the rows grew, which
     * pushed the whole table onto its own page. Zeroing it is the single change
     * that brings the table back in line with the PDF.
     */
    /**
     * A table style, as an ARRAY.
     *
     * addTable() takes a single argument, the style, and the style must be an
     * array: passing more arguments silently dropped the style, so every table
     * was built with no borders, no cell padding and no shading at all.
     *
     * Border sizes are in eighths of a point, Word's own unit, so 1 pt is 8.
     *
     * 'unit' and 'width' are the load-bearing keys, and they belong on the STYLE,
     * not on the element. Element\Table::setWidth() looks like it should work but
     * is dead in this version: the writer hands that value to a private field it
     * only reads in the string-style branch, and w:tblW is written from
     * Style\Table::getWidth() instead. A width set only through setWidth() is
     * discarded, and PhpWord's own default unit is TblWidth::AUTO, which emits
     * <w:tblW w:w="0" w:type="auto"/> and tells Word and LibreOffice to size the
     * table to its CONTENTS and ignore w:tblGrid entirely. That is what collapsed
     * the seven column attendance table from 17 cm to roughly 11 cm and crushed
     * the letterhead and signature block into a third of the page.
     *
     * An unruled table declares no border size at all, so hasBorder() is false and
     * no <w:tblBorders> is written. Passing 0 instead emits w:sz="0", a hairline
     * that LibreOffice draws and Word does not, which is how the letterhead and the
     * meeting detail table came out boxed in one renderer and open in the other.
     *
     * @return array<string,mixed>
     */
    private function tableStyle(bool $ruled = false, ?float $widthCm = null): array
    {
        $style = [
            'unit' => TblWidth::TWIP,
            'width' => DocumentLayout::cmToTwips($widthCm ?? DocumentLayout::contentWidthCm()),
            'layout' => 'fixed',
            'cellMarginLeft' => 0,
            'cellMarginRight' => 0,
            'cellMarginTop' => $ruled ? 40 : 0,
            'cellMarginBottom' => $ruled ? 40 : 0,
        ];

        if ($ruled) {
            $style['borderSize'] = DocumentLayout::ptToBorderEighths(DocumentLayout::BORDER_PT);
            $style['borderColor'] = DocumentLayout::BORDER_COLOR;
        }

        return $style;
    }

    /**
     * A row style that refuses to break across pages, and optionally repeats
     * as a header.
     *
     * Without this an attendance row can split so that its cells land on two
     * different pages, which is what left a stray NIP column alone on a page of
     * its own. PhpWord 0.18 keeps these on the row STYLE rather than the row
     * element, so they arrive as the second argument of addRow().
     *
     * @return array<string,mixed>
     */
    private function rowStyle(bool $header = false): array
    {
        $style = ['cantSplit' => true];

        if ($header) {
            $style['tblHeader'] = true;
        }

        return $style;
    }

    private function plain(?string $html): string
    {
        $text = preg_replace('/<[^>]+>/', ' ', (string) $html) ?? '';
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return htmlspecialchars(trim($text), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * An empty paragraph between two tables.
     *
     * OOXML requires a table to be followed by a paragraph, and both Word and
     * LibreOffice honour that literally: two <w:tbl> elements with nothing
     * between them are read as ONE table with the first table's grid and
     * borders. That is how the ruled attendance grid was drawn straight across
     * the borderless signature block underneath it, and how two grids with
     * different column counts would silently overwrite each other.
     *
     * The paragraph carries one point of type and no space before or after, so
     * it costs a point of height. Any real gap goes on top of it, through
     * $beforePt, rather than on the block that follows: a gap measured from the
     * wrong side lands on the wrong edge whenever the block reflows.
     */
    private function spacer(mixed $section, float $beforePt = 0): void
    {
        $section->addText('', ...$this->style(1, false, null, 'left', false, $beforePt));
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function logoWidthPt(array $data): ?float
    {
        $size = $data['logoSize'] ?? null;

        if (! is_array($size) || ($size[0] ?? 0) <= 0 || ($size[1] ?? 0) <= 0) {
            return null;
        }

        $scale = min(1.0, DocumentLayout::LOGO_BOX_PT / max($size[0], $size[1]));

        return round($size[0] * $scale, 1);
    }

    private function spill(?string $dataUri, string $extension): ?string
    {
        if (! is_string($dataUri) || ! str_contains($dataUri, 'base64,')) {
            return null;
        }

        $binary = base64_decode(substr($dataUri, strpos($dataUri, 'base64,') + 7), true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'siperapat_img_').'.'.$extension;
        file_put_contents($path, $binary);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    private function cleanTemporaryFiles(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }

        $this->temporaryFiles = [];
    }
}
