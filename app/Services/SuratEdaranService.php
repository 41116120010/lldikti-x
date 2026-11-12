<?php

namespace App\Services;

use App\Models\Agenda;
use App\Support\Html;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use RuntimeException;

class SuratEdaranService
{
    public function __construct(
        protected DompdfRenderer $dompdfRenderer
    ) {}

    /**
     * Save or update digital circular letter / meeting invitation.
     */
    public function saveDigitalSurat(Agenda $agenda, array $data, ?Request $request = null): Agenda
    {
        return DB::transaction(function () use ($agenda, $data, $request) {
            $resolved = $agenda->resolved_surat_edaran_config;
            $customLogoPath = $resolved['custom_logo_path'] ?? null;

            // Handle logo reset or upload
            if ($request?->boolean('reset_custom_logo')) {
                if ($customLogoPath && Storage::disk('public')->exists($customLogoPath)) {
                    Storage::disk('public')->delete($customLogoPath);
                }
                $customLogoPath = null;
            } elseif ($request?->hasFile('custom_logo')) {
                if ($customLogoPath && Storage::disk('public')->exists($customLogoPath)) {
                    Storage::disk('public')->delete($customLogoPath);
                }
                $customLogoPath = $request->file('custom_logo')->store("agendas/{$agenda->id}/logos", 'public');
            } elseif (! empty($data['custom_logo_path'])) {
                $customLogoPath = strip_tags((string) $data['custom_logo_path']);
            }

            $config = [
                'template_type' => in_array($data['template_type'] ?? 'undangan', ['undangan', 'edaran'], true)
                    ? $data['template_type']
                    : 'undangan',
                'show_kop' => isset($data['show_kop']) ? (bool) $data['show_kop'] : ($resolved['show_kop'] ?? true),
                'show_logo' => isset($data['show_logo']) ? (bool) $data['show_logo'] : ($resolved['show_logo'] ?? true),
                'custom_logo_path' => $customLogoPath,
                'instansi_induk' => strip_tags((string) ($data['instansi_induk'] ?? ($resolved['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI"))),
                'instansi_pelaksana' => strip_tags((string) ($data['instansi_pelaksana'] ?? ($resolved['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X'))),
                'alamat_kontak' => strip_tags((string) ($data['alamat_kontak'] ?? ($resolved['alamat_kontak'] ?? "Jalan Khatib Sulaiman Padang 25137\nTelepon (0751) 70567376\nLaman lldikti10.kemdiktisaintek.go.id  Pos-el info.lldikti10@kemdiktisaintek.go.id"))),
                'nomor_surat' => strip_tags((string) ($data['nomor_surat'] ?? ($resolved['nomor_surat'] ?? ''))),
                'sifat' => strip_tags((string) ($data['sifat'] ?? ($resolved['sifat'] ?? 'Biasa'))),
                'lampiran' => strip_tags((string) ($data['lampiran'] ?? ($resolved['lampiran'] ?? '-'))),
                'perihal' => strip_tags((string) ($data['perihal'] ?? ($resolved['perihal'] ?? ('Undangan Rapat '.$agenda->judul_rapat)))),
                'tempat_surat' => strip_tags((string) ($data['tempat_surat'] ?? ($resolved['tempat_surat'] ?? 'Padang'))),
                'tanggal_surat' => strip_tags((string) ($data['tanggal_surat'] ?? ($resolved['tanggal_surat'] ?? now()->translatedFormat('d F Y')))),
                'tujuan_surat' => strip_tags((string) ($data['tujuan_surat'] ?? ($resolved['tujuan_surat'] ?? ''))),
                'pembuka' => Html::sanitize((string) ($data['pembuka'] ?? ($resolved['pembuka'] ?? ''))),
                'catatan' => Html::sanitize((string) ($data['catatan'] ?? ($resolved['catatan'] ?? ''))),
                'penutup' => Html::sanitize((string) ($data['penutup'] ?? ($resolved['penutup'] ?? ''))),
                'signer_role' => strip_tags((string) ($data['signer_role'] ?? ($resolved['signer_role'] ?? 'Kepala'))),
                'signer_name' => strip_tags((string) ($data['signer_name'] ?? ($resolved['signer_name'] ?? 'Afdalisma'))),
                'signer_nip' => strip_tags((string) ($data['signer_nip'] ?? ($resolved['signer_nip'] ?? '197012051992032002'))),
                'show_signature' => isset($data['show_signature']) ? (bool) $data['show_signature'] : ($resolved['show_signature'] ?? true),
                'tembusan' => strip_tags((string) ($data['tembusan'] ?? ($resolved['tembusan'] ?? ''))),
                'show_lampiran' => isset($data['show_lampiran']) ? (bool) $data['show_lampiran'] : ($resolved['show_lampiran'] ?? false),
                'lampiran_content' => Html::sanitize((string) ($data['lampiran_content'] ?? ($resolved['lampiran_content'] ?? ''))),
            ];

            $content = Html::sanitize((string) ($data['surat_edaran_content'] ?? $agenda->surat_edaran_content ?? ''));

            $updatePayload = [
                'surat_edaran_type' => 'digital',
                'surat_edaran_content' => $content,
                'surat_edaran_config' => $config,
            ];

            $agenda->update($updatePayload);
            $agenda->refresh();

            // Auto-generate PDF and store path for mobile & download compatibility
            $this->generateAndStorePdf($agenda);

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_CIRCULAR',
                description: "Naskah surat dinas rapat '{$agenda->judul_rapat}' berhasil diperbarui.",
                targetModel: Agenda::class,
                targetId: $agenda->id
            );

            return $agenda;
        });
    }

    /**
     * Render the official HTML view for PDF generation or print.
     */
    public function renderHtml(Agenda $agenda): string
    {
        $config = $agenda->resolved_surat_edaran_config;

        $logoDataUri = null;
        if (! empty($config['show_logo'])) {
            $customLogo = $config['custom_logo_path'] ?? null;
            if ($customLogo && Storage::disk('public')->exists($customLogo)) {
                $fullPath = Storage::disk('public')->path($customLogo);
                $logoDataUri = $this->fileToDataUri($fullPath);
            } else {
                $bundled = public_path('images/tut-wuri-handayani.png');
                if (is_file($bundled)) {
                    $logoDataUri = $this->fileToDataUri($bundled);
                }
            }
        }

        return view('agendas.pdf.surat_edaran', [
            'agenda' => $agenda,
            'config' => $config,
            'logoDataUri' => $logoDataUri,
        ])->render();
    }

    /**
     * Render raw PDF bytes using Dompdf.
     */
    public function renderPdf(Agenda $agenda): string
    {
        $html = $this->renderHtml($agenda);

        return $this->dompdfRenderer->render($html);
    }

    /**
     * Alias for renderPdf() to maintain backward compatibility with mobile API endpoints.
     */
    public function generatePdf(Agenda $agenda): string
    {
        return $this->renderPdf($agenda);
    }

    /**
     * Generate PDF file and store it in storage/app/public/surat_edaran/
     */
    public function generateAndStorePdf(Agenda $agenda): ?string
    {
        try {
            $pdfBytes = $this->renderPdf($agenda);
            $relativePath = "surat_edaran/surat_digital_{$agenda->id}.pdf";

            Storage::disk('public')->put($relativePath, $pdfBytes);

            if ($agenda->surat_edaran_path !== $relativePath) {
                $agenda->update(['surat_edaran_path' => $relativePath]);
            }

            return $relativePath;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Return a binary PDF download or inline preview response.
     */
    public function downloadPdfResponse(Agenda $agenda, bool $forceDownload = false): Response
    {
        $pdfBytes = null;

        // Serve cached digital PDF from storage if present and valid, otherwise render & cache
        if ($agenda->is_surat_edaran_digital && $agenda->surat_edaran_path && Storage::disk('public')->exists($agenda->surat_edaran_path)) {
            $cached = Storage::disk('public')->get($agenda->surat_edaran_path);
            if (is_string($cached) && str_starts_with($cached, '%PDF')) {
                $pdfBytes = $cached;
            }
        }

        if ($pdfBytes === null) {
            $pdfBytes = $this->renderPdf($agenda);

            if ($agenda->is_surat_edaran_digital && $agenda->exists) {
                try {
                    $relativePath = "surat_edaran/surat_digital_{$agenda->id}.pdf";
                    Storage::disk('public')->put($relativePath, $pdfBytes);
                    if ($agenda->surat_edaran_path !== $relativePath) {
                        $agenda->update(['surat_edaran_path' => $relativePath]);
                    }
                } catch (\Throwable) {
                    // Ignore storage write failure during read-only preview
                }
            }
        }

        $filename = 'Surat_Undangan_'.(Str::slug((string) $agenda->judul_rapat) ?: 'Rapat_'.($agenda->id ?? 'Resmi')).'.pdf';
        $disposition = $forceDownload ? 'attachment' : 'inline';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Render raw OpenXML (.docx) bytes for the digital circular letter / meeting invitation on fixed A4 paper.
     */
    public function renderDocx(Agenda $agenda): string
    {
        $config = $agenda->resolved_surat_edaran_config;

        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(12);

        // Fixed A4 Portrait (21.0 cm × 29.7 cm = 11906 × 16838 dxa)
        // Margins: Top 1.0cm (567 dxa), Right 2.0cm (1134 dxa), Bottom 2.5cm (1418 dxa), Left 3.0cm (1701 dxa)
        // Printable width = 16.0 cm = 9071 dxa
        $printableTwips = 9071;
        $section = $word->addSection([
            'paperSize' => 'A4',
            'orientation' => 'portrait',
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 567,
            'marginRight' => 1134,
            'marginBottom' => 1418,
            'marginLeft' => 1701,
        ]);

        // 1. Kop Surat Dinas Resmi (Times New Roman 16pt / 14pt Bold / 12pt)
        if (! empty($config['show_kop'])) {
            $this->addDocxKopSurat($section, $config, $printableTwips);
        }

        $instansiTemplate = $config['template_type'] ?? 'undangan';
        $tujuanRaw = trim((string) ($config['tujuan_surat'] ?? ''));
        $tujuanFormatted = preg_replace('/^Yth\.?\s*/i', '', $tujuanRaw) ?? $tujuanRaw;
        $signerRoleClean = rtrim(trim((string) ($config['signer_role'] ?? 'Kepala')), ',');
        $signerNameClean = trim((string) ($config['signer_name'] ?? 'Afdalisma'));
        $signerNipClean = trim((string) ($config['signer_nip'] ?? '197012051992032002'));

        if ($instansiTemplate === 'edaran') {
            // Format Surat Edaran Resmi
            $section->addText(
                $this->xmlSafe('SURAT EDARAN'),
                ['name' => 'Arial', 'size' => 12, 'bold' => true, 'underline' => 'single'],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 120, 'spaceAfter' => 40]
            );
            $section->addText(
                $this->xmlSafe('NOMOR '.($config['nomor_surat'] ?? '-')),
                ['name' => 'Arial', 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 40]
            );
            $section->addText(
                $this->xmlSafe('TENTANG'),
                ['name' => 'Arial', 'size' => 12, 'bold' => true],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 20]
            );
            $section->addText(
                $this->xmlSafe(mb_strtoupper((string) ($config['perihal'] ?? $agenda->judul_rapat))),
                ['name' => 'Arial', 'size' => 12, 'bold' => true],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 200]
            );

            $this->addMultilineText($section, 'Yth. '.$tujuanFormatted, ['name' => 'Arial', 'size' => 12], ['spaceAfter' => 160]);

            if (! empty($config['pembuka'])) {
                $this->appendHtmlBlocksToSection($section, (string) $config['pembuka']);
            }
            if (! empty($agenda->surat_edaran_content)) {
                $this->appendHtmlBlocksToSection($section, (string) $agenda->surat_edaran_content);
            }
            if (! empty($config['penutup'])) {
                $this->appendHtmlBlocksToSection($section, (string) $config['penutup']);
            }

            $this->addDocxFooterSignature(
                $section,
                $config,
                $signerRoleClean,
                $signerNameClean,
                $signerNipClean,
                $printableTwips,
                true
            );
        } else {
            // Format Surat Undangan Rapat Kedinasan
            $metaTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'none',
                'cellMarginTop' => 20,
                'cellMarginBottom' => 20,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
                'alignment' => JcTable::START,
                'width' => $printableTwips,
                'unit' => TblWidth::TWIP,
                'layout' => 'fixed',
            ]);

            $c1 = (int) round($printableTwips * 0.135);
            $c2 = (int) round($printableTwips * 0.031);
            $c3 = (int) round($printableTwips * 0.427);
            $c4 = $printableTwips - $c1 - $c2 - $c3;

            $noStyle = ['borderSize' => 0, 'borderColor' => 'none', 'valign' => 'top'];
            $fontMeta = ['name' => 'Arial', 'size' => 12];
            $paraLeft = ['alignment' => Jc::START, 'spaceBefore' => 0, 'spaceAfter' => 20];
            $paraRight = ['alignment' => Jc::END, 'spaceBefore' => 0, 'spaceAfter' => 20];

            $row1 = $metaTable->addRow();
            $row1->addCell($c1, $noStyle)->addText('Nomor', $fontMeta, $paraLeft);
            $row1->addCell($c2, $noStyle)->addText(':', $fontMeta, $paraLeft);
            $row1->addCell($c3, $noStyle)->addText($this->xmlSafe((string) ($config['nomor_surat'] ?? '-')), $fontMeta, $paraLeft);
            $row1->addCell($c4, $noStyle)->addText($this->xmlSafe((string) ($config['tanggal_surat'] ?? now()->translatedFormat('d F Y'))), $fontMeta, $paraRight);

            $row2 = $metaTable->addRow();
            $row2->addCell($c1, $noStyle)->addText('Lampiran', $fontMeta, $paraLeft);
            $row2->addCell($c2, $noStyle)->addText(':', $fontMeta, $paraLeft);
            $row2->addCell($c3 + $c4, array_merge($noStyle, ['gridSpan' => 2]))
                ->addText($this->xmlSafe((string) ($config['lampiran'] ?? '-')), $fontMeta, $paraLeft);

            $row3 = $metaTable->addRow();
            $row3->addCell($c1, $noStyle)->addText('Hal', $fontMeta, $paraLeft);
            $row3->addCell($c2, $noStyle)->addText(':', $fontMeta, $paraLeft);
            $row3->addCell($c3 + $c4, array_merge($noStyle, ['gridSpan' => 2]))
                ->addText($this->xmlSafe((string) ($config['perihal'] ?? $agenda->judul_rapat)), $fontMeta, [
                    'alignment' => Jc::BOTH,
                    'spaceBefore' => 0,
                    'spaceAfter' => 20,
                ]);

            $this->addTableSpacer($section);

            $this->addMultilineText($section, 'Yth. '.$tujuanFormatted, $fontMeta, [
                'alignment' => Jc::BOTH,
                'spaceBefore' => 160,
                'spaceAfter' => 160,
            ]);

            if (! empty($config['pembuka'])) {
                $this->appendHtmlBlocksToSection($section, (string) $config['pembuka']);
            } else {
                $section->addText(
                    $this->xmlSafe('Dalam rangka pelaksanaan program kerja dan koordinasi pelaksanaan tugas kedinasan, dengan hormat kami mengharapkan kehadiran Bapak/Ibu pada'),
                    $fontMeta,
                    ['alignment' => Jc::BOTH, 'spaceBefore' => 0, 'spaceAfter' => 120]
                );
            }

            // Tabel Rincian Acara (hari, tanggal | pukul | tempat | acara)
            $detailTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'none',
                'cellMarginTop' => 30,
                'cellMarginBottom' => 30,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
                'alignment' => JcTable::START,
                'width' => $printableTwips,
                'unit' => TblWidth::TWIP,
                'layout' => 'fixed',
            ]);

            $d1 = (int) round($printableTwips * 0.176);
            $d2 = (int) round($printableTwips * 0.026);
            $d3 = $printableTwips - $d1 - $d2;

            $waktuText = ($agenda->waktu_mulai ? $agenda->waktu_mulai->format('H.i') : '-')
                .' '
                .($agenda->waktu_selesai ? 's.d. '.$agenda->waktu_selesai->format('H.i').' WIB' : 'WIB s.d. selesai');

            $tempatText = match ($agenda->tipe_rapat) {
                'online' => 'Daring (Online Meeting) — '.($agenda->link_meeting ?? 'Tautan rapat dibagikan melalui grup koordinasi'),
                'hybrid' => ($agenda->lokasi_ruang ?? 'Ruang Rapat LLDIKTI Wilayah X').' (Hybrid / Luring & Daring)',
                default => $agenda->lokasi_ruang ?? 'Ruang Rapat Utama LLDIKTI Wilayah X',
            };

            $details = [
                ['hari, tanggal', $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('l, d F Y') : '-'],
                ['pukul', $waktuText],
                ['tempat', $tempatText],
                ['acara', (string) $agenda->judul_rapat],
            ];

            foreach ($details as [$dLabel, $dVal]) {
                $dRow = $detailTable->addRow();
                $dRow->addCell($d1, $noStyle)->addText($this->xmlSafe($dLabel), $fontMeta, $paraLeft);
                $dRow->addCell($d2, $noStyle)->addText(':', $fontMeta, $paraLeft);
                $dRow->addCell($d3, $noStyle)->addText($this->xmlSafe($dVal), $fontMeta, $paraLeft);
            }

            $this->addTableSpacer($section);

            if (! empty($agenda->surat_edaran_content)) {
                $this->appendHtmlBlocksToSection($section, (string) $agenda->surat_edaran_content);
            }
            if (! empty($config['catatan'])) {
                $this->appendHtmlBlocksToSection($section, (string) $config['catatan']);
            }

            if (! empty($config['penutup'])) {
                $this->appendHtmlBlocksToSection($section, (string) $config['penutup']);
            } else {
                $section->addText(
                    $this->xmlSafe('Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.'),
                    $fontMeta,
                    ['alignment' => Jc::BOTH, 'spaceBefore' => 80, 'spaceAfter' => 160]
                );
            }

            $this->addDocxFooterSignature(
                $section,
                $config,
                $signerRoleClean,
                $signerNameClean,
                $signerNipClean,
                $printableTwips,
                false
            );
        }

        // Halaman 2: Lampiran Daftar Undangan (jika diaktifkan)
        $hasLampiranPage = ! empty($config['show_lampiran']) || (! empty($config['lampiran']) && trim((string) $config['lampiran']) !== '-');
        if ($hasLampiranPage && ! empty($config['lampiran_content'])) {
            $section->addPageBreak();
            $section->addText(
                '- 2 -',
                ['name' => 'Times New Roman', 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 240]
            );
            $section->addText(
                'Lampiran Surat',
                ['name' => 'Arial', 'size' => 12],
                ['alignment' => Jc::START, 'spaceBefore' => 0, 'spaceAfter' => 40]
            );

            $lampiranMeta = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'none',
                'cellMarginTop' => 20,
                'cellMarginBottom' => 20,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
                'alignment' => JcTable::START,
                'width' => $printableTwips,
                'unit' => TblWidth::TWIP,
                'layout' => 'fixed',
            ]);
            $l1 = (int) round($printableTwips * 0.111);
            $l2 = (int) round($printableTwips * 0.031);
            $l3 = $printableTwips - $l1 - $l2;
            $noStyle = ['borderSize' => 0, 'borderColor' => 'none', 'valign' => 'top'];
            $fontMeta = ['name' => 'Arial', 'size' => 12];
            $paraLeft = ['alignment' => Jc::START, 'spaceBefore' => 0, 'spaceAfter' => 20];

            $lr1 = $lampiranMeta->addRow();
            $lr1->addCell($l1, $noStyle)->addText('Nomor', $fontMeta, $paraLeft);
            $lr1->addCell($l2, $noStyle)->addText(':', $fontMeta, $paraLeft);
            $lr1->addCell($l3, $noStyle)->addText($this->xmlSafe((string) ($config['nomor_surat'] ?? '-')), $fontMeta, $paraLeft);

            $lr2 = $lampiranMeta->addRow();
            $lr2->addCell($l1, $noStyle)->addText('Tanggal', $fontMeta, $paraLeft);
            $lr2->addCell($l2, $noStyle)->addText(':', $fontMeta, $paraLeft);
            $lr2->addCell($l3, $noStyle)->addText($this->xmlSafe((string) ($config['tanggal_surat'] ?? now()->translatedFormat('d F Y'))), $fontMeta, $paraLeft);

            $this->addTableSpacer($section);

            $section->addText(
                'Daftar Undangan',
                ['name' => 'Arial', 'size' => 12],
                ['alignment' => Jc::START, 'spaceBefore' => 160, 'spaceAfter' => 120]
            );

            $this->appendHtmlBlocksToSection($section, (string) $config['lampiran_content']);
        }

        $tempDocx = tempnam(sys_get_temp_dir(), 'siperapat_surat_docx_');
        if ($tempDocx === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk ekspor Word Surat Dinas.');
        }

        try {
            IOFactory::createWriter($word, 'Word2007')->save($tempDocx);
            $bytes = file_get_contents($tempDocx);
            if ($bytes === false) {
                throw new RuntimeException('Gagal membaca hasil ekspor Word Surat Dinas.');
            }

            return $bytes;
        } finally {
            @unlink($tempDocx);
        }
    }

    /**
     * Return a binary OpenXML (.docx) download response for the circular letter / meeting invitation.
     */
    public function downloadWordResponse(Agenda $agenda): Response
    {
        $docxBytes = $this->renderDocx($agenda);
        $filename = 'Surat_Undangan_'.(Str::slug((string) $agenda->judul_rapat) ?: 'Rapat_'.($agenda->id ?? 'Resmi')).'.docx';

        return response($docxBytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function addDocxKopSurat($section, array $config, int $printableTwips): void
    {
        $instansiIndukRaw = trim((string) ($config['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI"));
        if ($instansiIndukRaw === 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI') {
            $instansiIndukLines = ['KEMENTERIAN PENDIDIKAN TINGGI,', 'SAINS, DAN TEKNOLOGI'];
        } else {
            $instansiIndukLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $instansiIndukRaw))));
        }

        $instansiPelaksana = trim((string) ($config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X'));
        $alamatRaw = trim((string) ($config['alamat_kontak'] ?? "Jalan Khatib Sulaiman Padang 25137\nTelepon (0751) 70567376\nLaman lldikti10.kemdiktisaintek.go.id  Pos-el info.lldikti10@kemdiktisaintek.go.id"));
        $alamatLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $alamatRaw))));

        $logoPath = null;
        if (! empty($config['show_logo'])) {
            $customLogo = $config['custom_logo_path'] ?? null;
            if ($customLogo && Storage::disk('public')->exists($customLogo)) {
                $candidate = Storage::disk('public')->path($customLogo);
                if (is_file($candidate)) {
                    $logoPath = $candidate;
                }
            }
            if ($logoPath === null) {
                $bundled = public_path('images/tut-wuri-handayani.png');
                if (is_file($bundled)) {
                    $logoPath = $bundled;
                }
            }
        }

        $kopTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'none',
            'cellMarginTop' => 0,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 0,
            'cellMarginRight' => 0,
            'alignment' => JcTable::START,
            'width' => $printableTwips,
            'unit' => TblWidth::TWIP,
            'layout' => 'fixed',
        ]);

        $row = $kopTable->addRow();
        $bottomRuleCell = [
            'borderTopSize' => 0,
            'borderLeftSize' => 0,
            'borderRightSize' => 0,
            'borderBottomSize' => 18, // 2.25pt solid black rule
            'borderBottomColor' => '000000',
            'valign' => 'center',
        ];

        if ($logoPath !== null) {
            $logoColTwips = (int) round($printableTwips * 0.197);
            $textColTwips = $printableTwips - $logoColTwips;

            $logoCell = $row->addCell($logoColTwips, $bottomRuleCell);
            $logoCell->addImage($logoPath, [
                'width' => 72,
                'height' => 72,
                'alignment' => Jc::CENTER,
            ]);
            $textCell = $row->addCell($textColTwips, $bottomRuleCell);
        } else {
            $textCell = $row->addCell($printableTwips, $bottomRuleCell);
        }

        foreach ($instansiIndukLines as $line) {
            $textCell->addText(
                $this->xmlSafe(mb_strtoupper($line)),
                ['name' => 'Times New Roman', 'size' => 16, 'bold' => false],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
            );
        }

        $textCell->addText(
            $this->xmlSafe(mb_strtoupper($instansiPelaksana)),
            ['name' => 'Times New Roman', 'size' => 14, 'bold' => true],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 20, 'spaceAfter' => 20]
        );

        foreach ($alamatLines as $addrLine) {
            $textCell->addText(
                $this->xmlSafe($addrLine),
                ['name' => 'Times New Roman', 'size' => 12, 'bold' => false],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
            );
        }

        $this->addTableSpacer($section, 160);
    }

    private function addDocxFooterSignature(
        $section,
        array $config,
        string $signerRoleClean,
        string $signerNameClean,
        string $signerNipClean,
        int $printableTwips,
        bool $isEdaran
    ): void {
        $this->addTableSpacer($section, 180);

        $footerTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'none',
            'cellMarginTop' => 20,
            'cellMarginBottom' => 20,
            'cellMarginLeft' => 0,
            'cellMarginRight' => 0,
            'alignment' => JcTable::START,
            'width' => $printableTwips,
            'unit' => TblWidth::TWIP,
            'layout' => 'fixed',
        ]);

        $leftTwips = (int) round($printableTwips * 0.555);
        $rightTwips = $printableTwips - $leftTwips;
        $noBorder = ['borderSize' => 0, 'borderColor' => 'none', 'valign' => 'top'];
        $font = ['name' => 'Arial', 'size' => 12];
        $para = ['alignment' => Jc::START, 'spaceBefore' => 0, 'spaceAfter' => 20];

        $row1 = $footerTable->addRow(null, ['cantSplit' => true]);
        $row1->addCell($leftTwips, $noBorder)->addText('', $font, $para);
        $rightCell = $row1->addCell($rightTwips, $noBorder);

        if ($isEdaran) {
            $rightCell->addText($this->xmlSafe('Ditetapkan di '.($config['tempat_surat'] ?? 'Padang')), $font, $para);
            $rightCell->addText($this->xmlSafe('pada tanggal '.($config['tanggal_surat'] ?? now()->translatedFormat('d F Y'))), $font, ['alignment' => Jc::START, 'spaceBefore' => 0, 'spaceAfter' => 80]);
        }

        $rightCell->addText($this->xmlSafe($signerRoleClean.','), $font, $para);

        if (! empty($config['show_signature'])) {
            $rightCell->addText(
                $this->xmlSafe('[Tanda Tangan Digital Tersertifikasi SIPERAPAT]'),
                ['name' => 'Arial', 'size' => 8, 'bold' => true, 'color' => '1E3A8A'],
                ['alignment' => Jc::START, 'spaceBefore' => 240, 'spaceAfter' => 240]
            );
        } else {
            $rightCell->addText('', $font, ['spaceBefore' => 360, 'spaceAfter' => 360]);
        }

        $rightCell->addText($this->xmlSafe($signerNameClean), $font, $para);

        $tembusanRaw = trim((string) ($config['tembusan'] ?? ''));
        $row2 = $footerTable->addRow(null, ['cantSplit' => true]);
        $leftNipCell = $row2->addCell($leftTwips, $noBorder);
        if ($tembusanRaw !== '') {
            $leftNipCell->addText('Tembusan:', $font, $para);
        } else {
            $leftNipCell->addText('', $font, $para);
        }

        $rightNipCell = $row2->addCell($rightTwips, $noBorder);
        $nipRun = $rightNipCell->addTextRun($para);
        $nipRun->addText('NIP ', $font);
        $nipRun->addText($this->xmlSafe($signerNipClean), ['name' => 'Arial', 'size' => 12, 'bold' => true]);

        if ($tembusanRaw !== '') {
            $row3 = $footerTable->addRow(null, ['cantSplit' => true]);
            $tembusanCell = $row3->addCell($printableTwips, array_merge($noBorder, ['gridSpan' => 2]));
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $tembusanRaw))));
            foreach ($lines as $tLine) {
                $tembusanCell->addText($this->xmlSafe($tLine), $font, $para);
            }
        }
    }

    private function appendHtmlBlocksToSection($section, string $html): void
    {
        $clean = trim($html);
        if ($clean === '') {
            return;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="utf-8" ?><div>'.$clean.'</div>';
        @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (! $root instanceof DOMElement) {
            $this->addMultilineText($section, strip_tags($clean), ['name' => 'Arial', 'size' => 12], [
                'alignment' => Jc::BOTH,
                'spaceBefore' => 0,
                'spaceAfter' => 120,
            ]);

            return;
        }

        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                $text = trim($node->textContent);
                if ($text !== '') {
                    $section->addText($this->xmlSafe($text), ['name' => 'Arial', 'size' => 12], [
                        'alignment' => Jc::BOTH,
                        'spaceBefore' => 0,
                        'spaceAfter' => 120,
                    ]);
                }

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if ($tag === 'ul' || $tag === 'ol') {
                $idx = 1;
                foreach ($node->childNodes as $li) {
                    if ($li instanceof DOMElement && strtolower($li->tagName) === 'li') {
                        $prefix = $tag === 'ol' ? ($idx.'. ') : '• ';
                        $liText = trim(preg_replace('/\s+/u', ' ', $li->textContent) ?? '');
                        if ($liText !== '') {
                            $section->addText(
                                $this->xmlSafe($prefix.$liText),
                                ['name' => 'Arial', 'size' => 12],
                                ['alignment' => Jc::BOTH, 'spaceBefore' => 0, 'spaceAfter' => 40, 'indentation' => ['left' => 360, 'hanging' => 360]]
                            );
                            $idx++;
                        }
                    }
                }

                continue;
            }

            $run = $section->addTextRun([
                'alignment' => Jc::BOTH,
                'spaceBefore' => 0,
                'spaceAfter' => 120,
            ]);
            $this->appendInlineNodesToRun($run, $node, ['name' => 'Arial', 'size' => 12]);
        }
    }

    private function appendInlineNodesToRun($run, DOMNode $parent, array $style): void
    {
        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = preg_replace('/[ \t]+/u', ' ', $child->nodeValue ?? '') ?? '';
                if ($text !== '') {
                    $run->addText($this->xmlSafe($text), $style);
                }

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if ($tag === 'br') {
                $run->addTextBreak();

                continue;
            }

            $nextStyle = $style;
            if (in_array($tag, ['b', 'strong'], true)) {
                $nextStyle['bold'] = true;
            } elseif (in_array($tag, ['i', 'em'], true)) {
                $nextStyle['italic'] = true;
            } elseif ($tag === 'u') {
                $nextStyle['underline'] = 'single';
            }

            $this->appendInlineNodesToRun($run, $child, $nextStyle);
        }
    }

    private function addMultilineText($section, string $text, array $fontStyle, array $paraStyle): void
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        foreach ($lines as $idx => $line) {
            $isLast = $idx === count($lines) - 1;
            $pStyle = $paraStyle;
            if (! $isLast) {
                $pStyle['spaceAfter'] = 20;
            }
            $section->addText($this->xmlSafe(trim($line)), $fontStyle, $pStyle);
        }
    }

    private function addTableSpacer($section, int $spaceAfter = 0): void
    {
        $section->addText('', ['size' => 1], [
            'spaceBefore' => 0,
            'spaceAfter' => $spaceAfter,
            'lineHeight' => 1.0,
        ]);
    }

    private function xmlSafe(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * Convert an image file path to a base64 Data URI.
     */
    protected function fileToDataUri(string $filePath): ?string
    {
        if (! is_file($filePath)) {
            return null;
        }

        $mime = mime_content_type($filePath) ?: 'image/png';
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
