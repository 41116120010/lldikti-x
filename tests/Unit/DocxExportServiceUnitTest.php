<?php

namespace Tests\Unit;

use App\Models\Agenda;
use App\Models\User;
use App\Services\DocxExportService;
use App\Services\WordExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Settings;

class DocxExportServiceUnitTest extends BaseTestCase
{
    private function createMockAgenda(): Agenda
    {
        $creator = new User([
            'name' => 'Dr. Penyelenggara Rapat',
            'nip' => '197501012000011001',
        ]);

        $agenda = new Agenda([
            'id' => 999,
            'judul_rapat' => 'Rapat Koordinasi Evaluasi Perencanaan Anggaran',
            'slug' => 'rakor-evaluasi-anggaran-2026',
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => 'Ruang Rapat Utama LLDIKTI',
            'waktu_mulai' => Carbon::parse('2026-11-09 09:00:00'),
            'waktu_selesai' => Carbon::parse('2026-11-09 12:00:00'),
            'notulensi' => '<p>Notulensi rapat koordinasi mengenai tindak lanjut program kerja.</p>',
            'kesimpulan' => '<p>Kesimpulan disepakati seluruh peserta.</p>',
        ]);
        $agenda->id = 999;
        $agenda->setRelation('creator', $creator);

        return $agenda;
    }

    private function createMockViewData(Agenda $agenda, ?Collection $attendances = null): array
    {
        if ($attendances === null) {
            $user = new User([
                'name' => 'Budi Santoso, S.Kom',
                'nip' => '198502022010011002',
            ]);

            $attendanceModel = (object) [
                'user' => $user,
                'signed_at' => Carbon::parse('2026-11-09 09:15:00'),
            ];

            $attendances = collect([
                [
                    'model' => $attendanceModel,
                    'sig_base64' => null,
                    'selfie_base64' => null,
                ],
            ]);
        }

        return [
            'agenda' => $agenda,
            'config' => [
                'show_kop' => true,
                'show_logo' => false,
                'show_nip' => true,
                'show_unit' => true,
                'show_attendance_time' => true,
                'show_selfie_photos' => true,
                'show_attendee_signatures' => true,
                'show_signatures' => true,
                'show_footer_note' => true,
                'show_documentation_photos' => true,
            ],
            'pageMode' => 'named',
            'logoBase64' => null,
            'logoSize' => null,
            'attendances' => $attendances,
            'documentations' => collect([]),
            'pimpinanSigBase64' => null,
            'notulisSigBase64' => null,
            'generatedAt' => Carbon::now(),
        ];
    }

    public function test_builds_docx_with_standard_ziparchive_engine(): void
    {
        $agenda = $this->createMockAgenda();
        $viewData = $this->createMockViewData($agenda);

        $wordServiceMock = $this->createMock(WordExportService::class);
        $wordServiceMock->method('prepareViewData')
            ->willReturn($viewData);

        $docxService = new DocxExportService($wordServiceMock);
        $response = $docxService->exportBeritaAcara($agenda);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Berita_Acara_rakor-evaluasi-anggaran-2026.docx', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringStartsWith('PK', $content, 'Output harus berupa ZIP archive (.docx)');
    }

    public function test_builds_docx_with_pclzip_fallback_engine(): void
    {
        $agenda = $this->createMockAgenda();
        $viewData = $this->createMockViewData($agenda);

        $wordServiceMock = $this->createMock(WordExportService::class);
        $wordServiceMock->method('prepareViewData')
            ->willReturn($viewData);

        // Force PclZip engine directly
        Settings::setZipClass(Settings::PCLZIP);

        $docxService = new DocxExportService($wordServiceMock);
        $response = $docxService->exportBeritaAcara($agenda);

        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringStartsWith('PK', $content, 'Output PclZip harus berupa ZIP archive (.docx) valid');
    }

    public function test_survives_null_user_on_attendance_record(): void
    {
        $agenda = $this->createMockAgenda();

        // Attendance with null user (e.g. deleted or anomalous relation)
        $brokenAttendance = (object) [
            'user' => null,
            'signed_at' => Carbon::parse('2026-11-09 09:20:00'),
        ];

        $viewData = $this->createMockViewData($agenda, collect([
            [
                'model' => $brokenAttendance,
                'sig_base64' => null,
                'selfie_base64' => null,
            ],
        ]));

        $wordServiceMock = $this->createMock(WordExportService::class);
        $wordServiceMock->method('prepareViewData')
            ->willReturn($viewData);

        $docxService = new DocxExportService($wordServiceMock);
        $response = $docxService->exportBeritaAcara($agenda);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('PK', $response->getContent());
    }

    public function test_handles_webp_data_uri_without_crash(): void
    {
        $agenda = $this->createMockAgenda();

        // Small 1x1 WebP binary data URI
        $webpBase64 = 'data:image/webp;base64,UklGRkAAAABXRUJQVlA4IDQAAADwAQCdASoBAAEAAQAcJaACdLoB+AAA/v5e9gAAAAAAAAAAAA==';

        $attendanceModel = (object) [
            'user' => new User(['name' => 'WebP User', 'nip' => '123456789']),
            'signed_at' => Carbon::now(),
        ];

        $viewData = $this->createMockViewData($agenda, collect([
            [
                'model' => $attendanceModel,
                'sig_base64' => null,
                'selfie_base64' => $webpBase64,
            ],
        ]));

        $wordServiceMock = $this->createMock(WordExportService::class);
        $wordServiceMock->method('prepareViewData')
            ->willReturn($viewData);

        $docxService = new DocxExportService($wordServiceMock);
        $response = $docxService->exportBeritaAcara($agenda);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('PK', $response->getContent());
    }

    public function test_cleans_temporary_files_after_export(): void
    {
        $agenda = $this->createMockAgenda();

        // Image with data URI to force spill() to allocate temporary files
        $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $attendanceModel = (object) [
            'user' => new User(['name' => 'Peserta Temp Clean', 'nip' => '123456789']),
            'signed_at' => Carbon::now(),
        ];

        $viewData = $this->createMockViewData($agenda, collect([
            [
                'model' => $attendanceModel,
                'sig_base64' => $pngBase64,
                'selfie_base64' => $pngBase64,
            ],
        ]));

        $wordServiceMock = $this->createMock(WordExportService::class);
        $wordServiceMock->method('prepareViewData')
            ->willReturn($viewData);

        $docxService = new DocxExportService($wordServiceMock);

        $tmpBefore = glob(sys_get_temp_dir().'/siperapat_*') ?: [];

        $response = $docxService->exportBeritaAcara($agenda);

        $this->assertSame(200, $response->getStatusCode());

        $tmpAfter = glob(sys_get_temp_dir().'/siperapat_*') ?: [];
        $newLeakedFiles = array_diff($tmpAfter, $tmpBefore);

        $this->assertEmpty($newLeakedFiles, 'Tidak boleh ada file sementara siperapat_* yang tertinggal di /tmp');
    }
}
