<?php

namespace App\Services;

use App\Models\Agenda;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                'perihal' => strip_tags((string) ($data['perihal'] ?? ($resolved['perihal'] ?? ('Undangan Rapat ' . $agenda->judul_rapat)))),
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
     * Return a binary PDF download response.
     */
    public function downloadPdfResponse(Agenda $agenda): Response
    {
        $pdfBytes = $this->renderPdf($agenda);
        $filename = 'Surat_Undangan_' . Str::slug($agenda->judul_rapat) . '.pdf';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
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

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}
