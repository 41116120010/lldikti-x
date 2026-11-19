<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_list_visible_agendas(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/agendas');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'judul_rapat',
                        'slug',
                        'status' => ['value', 'label'],
                        'waktu_mulai',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_administrator_can_create_agenda_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $payload = [
            'judul_rapat' => 'Rapat Koordinasi Perencanaan Anggaran Triwulan IV',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'luring',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(2)->addHours(3)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.judul_rapat', $payload['judul_rapat'])
            ->assertJsonPath('data.tipe_rapat', 'offline');
    }

    public function test_staff_cannot_create_agenda_via_api(): void
    {
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($staff);

        $payload = [
            'judul_rapat' => 'Percobaan Bikin Rapat Oleh Staff',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'daring',
            'waktu_mulai' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ];

        $response = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/v1/agendas', $payload);

        $response->assertStatus(403);
    }

    public function test_user_can_view_agenda_details_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $agenda->id)
            ->assertJsonPath('data.judul_rapat', $agenda->judul_rapat)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'judul_rapat',
                    'status' => ['value', 'label'],
                    'surat_edaran',
                    'creator',
                    'nomor_ba',
                    'total_invited',
                    'total_attended',
                    'unattended_count',
                    'participation_rate',
                    'participation_rate_label',
                    'export_links' => [
                        'pdf',
                        'word',
                        'csv',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_agenda_status_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status.value', 'completed');
    }

    public function test_admin_can_assign_roles_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);
        $this->assertNotNull($staff);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/agendas/{$agenda->id}/roles", [
                'pimpinan_id' => $admin->id,
                'notulis_id' => $staff->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pimpinan.id', $admin->id)
            ->assertJsonPath('data.notulis.id', $staff->id);
    }

    public function test_authorized_user_can_update_minutes_and_upload_photos_via_api(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);
        $initialDocCount = $agenda->documentations()->count();

        $photo = UploadedFile::fake()->image('dokumentasi_rapat.jpg', 800, 600);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/agendas/{$agenda->id}/notulen", [
                'notulensi' => '<p>Rapat koordinasi membahas target kinerja triwulan IV berjalan lancar.</p>',
                'kesimpulan' => '<p>Seluruh unit wajib menyelesaikan laporan sebelum tanggal 20.</p>',
                'photos' => [$photo],
                'captions' => ['Foto sesi diskusi pembukaan'],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $agenda->id);

        $agenda->refresh();
        $this->assertStringContainsString('Rapat koordinasi membahas target kinerja', $agenda->notulensi);
        $this->assertStringContainsString('Seluruh unit wajib menyelesaikan laporan', $agenda->kesimpulan);
        $this->assertCount($initialDocCount + 1, $agenda->documentations);
    }

    public function test_authorized_user_can_delete_documentation_photo_via_api(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $doc = $agenda->documentations()->first();
        $this->assertNotNull($doc);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/agendas/{$agenda->id}/documentations/{$doc->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Foto dokumentasi kegiatan berhasil dihapus.');

        $this->assertDatabaseMissing('agenda_documentations', [
            'id' => $doc->id,
        ]);
    }

    public function test_unauthorized_user_cannot_update_minutes_via_api(): void
    {
        // An unrelated staff member who is not notulis or admin of the agenda
        $unrelatedStaff = User::where('role', 'staff')
            ->where('id', '!=', Agenda::first()->notulis_id)
            ->first();

        if ($unrelatedStaff) {
            $agenda = Agenda::first();
            $response = $this->actingAs($unrelatedStaff, 'sanctum')
                ->putJson("/api/v1/agendas/{$agenda->id}/notulen", [
                    'notulensi' => '<p>Ubah tanpa izin</p>',
                ]);

            $response->assertStatus(403);
        }
    }

    public function test_authorized_admin_can_update_agenda_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/agendas/{$agenda->id}", [
                'judul_rapat' => 'Judul Agenda Rapat Diperbarui',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'hybrid',
                'lokasi_ruang' => 'Ruang Sidang Utama Lantai 3',
                'link_meeting' => 'https://zoom.us/j/999888777',
                'waktu_mulai' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'is_all_units' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.judul_rapat', 'Judul Agenda Rapat Diperbarui');

        $this->assertEquals('Judul Agenda Rapat Diperbarui', $agenda->fresh()->judul_rapat);
    }

    public function test_authorized_admin_can_delete_agenda_without_attendances_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        // Create a temporary standalone agenda without attendees
        $agenda = Agenda::create([
            'created_by' => $admin->id,
            'judul_rapat' => 'Agenda Siap Hapus Tanpa Kehadiran',
            'slug' => 'agenda-siap-hapus',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/111222333',
            'waktu_mulai' => now()->addDays(5),
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/agendas/{$agenda->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('agendas', [
            'id' => $agenda->id,
        ]);
    }

    public function test_admin_cannot_delete_agenda_with_attendances_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);
        $this->assertTrue($agenda->attendances()->exists());

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/agendas/{$agenda->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
        ]);
    }

    public function test_agenda_filtering_and_search_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/agendas?status={$agenda->status}&tipe_rapat={$agenda->tipe_rapat}&search=Evaluasi");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'summary' => [
                    'status_counts' => [
                        'all',
                        'draft',
                        'ongoing',
                        'scheduled',
                        'completed',
                        'cancelled',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_authorized_user_can_fetch_notulen_workstation_and_upload_documentation_via_api(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($agenda);

        // 1. Fetch GET /api/v1/agendas/{agenda}/notulen workstation payload
        $notulenRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}/notulen");

        $notulenRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'agenda',
                    'notulensi_raw',
                    'kesimpulan_raw',
                    'report_config',
                    'documentations',
                    'attendances',
                ],
            ]);

        // 2. Upload documentation photo directly via POST /api/v1/agendas/{agenda}/documentations
        $photo = UploadedFile::fake()->image('kamera_mobile.jpg', 800, 600);
        $uploadRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/agendas/{$agenda->id}/documentations", [
                'photo' => $photo,
                'caption' => 'Dokumentasi langsung dari kamera Android',
            ]);

        $uploadRes->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_agenda_detail_includes_permissions_and_streams_surat_edaran_via_api(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($agenda);

        Storage::disk('public')->put('surat-edaran/test-edaran.pdf', '%PDF-1.4 dummy content');
        $agenda->update(['surat_edaran_path' => 'surat-edaran/test-edaran.pdf']);

        $showRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/agendas/{$agenda->id}");

        $showRes->assertOk()
            ->assertJsonPath('data.permissions.can_update', true)
            ->assertJsonPath('data.permissions.can_manage_minutes', true)
            ->assertJsonPath('data.permissions.can_export', true);

        $this->assertNotEmpty($showRes->json('data.surat_edaran.stream_url'));

        $streamRes = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/agendas/{$agenda->id}/surat-edaran");

        $streamRes->assertOk();
    }

    public function test_administrator_can_create_agenda_with_until_finished_schedule_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $startAt = now()->addDays(14)->setTime(9, 0, 0);

        $payload = [
            'judul_rapat' => 'Rapat Evaluasi Hingga Selesai via API',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'luring',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'waktu_mulai' => $startAt->format('Y-m-d H:i:s'),
            'waktu_selesai' => null,
            'is_all_units' => true,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.waktu_selesai', null);

        $this->assertStringContainsString('WIB s.d. Selesai', (string) $response->json('data.rentang_waktu'));
    }

    public function test_administrator_can_update_agenda_from_fixed_end_time_to_until_finished_via_api(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $startAt = now()->addDays(15)->setTime(9, 0, 0);
        $fixedEndAt = $startAt->copy()->addHours(3);

        $agenda = Agenda::create([
            'created_by' => $admin->id,
            'judul_rapat' => 'Rapat Uji Transisi Hingga Selesai',
            'slug' => 'rapat-uji-transisi-hingga-selesai',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Teratai',
            'waktu_mulai' => $startAt,
            'waktu_selesai' => $fixedEndAt,
            'target_type' => 'all_units',
            'is_all_units' => true,
            'status' => 'scheduled',
        ]);

        // 1. Update via JSON PUT setting waktu_selesai => null
        $jsonResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/agendas/{$agenda->id}", [
                'judul_rapat' => $agenda->judul_rapat,
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Teratai',
                'waktu_mulai' => $startAt->format('Y-m-d H:i:s'),
                'waktu_selesai' => null,
                'is_all_units' => true,
            ]);

        $jsonResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.waktu_selesai', null);

        $agenda->refresh();
        $this->assertNull($agenda->waktu_selesai);
        $this->assertStringContainsString('WIB s.d. Selesai', $agenda->rentang_waktu);
        $this->assertStringContainsString('WIB s.d. Selesai', (string) $jsonResponse->json('data.rentang_waktu'));

        // 2. Restore fixed end time (+3 hours)
        $restoreResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/agendas/{$agenda->id}", [
                'judul_rapat' => $agenda->judul_rapat,
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Teratai',
                'waktu_mulai' => $startAt->format('Y-m-d H:i:s'),
                'waktu_selesai' => $fixedEndAt->format('Y-m-d H:i:s'),
                'is_all_units' => true,
            ]);

        $restoreResponse->assertOk();
        $agenda->refresh();
        $this->assertNotNull($agenda->waktu_selesai);

        // 3. Update back to until-finished via multipart POST (_method => PUT) with empty string waktu_selesai
        $pdf = UploadedFile::fake()->create('edaran.pdf', 100, 'application/pdf');
        $multipartResponse = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/agendas/{$agenda->id}", [
                '_method' => 'PUT',
                'judul_rapat' => $agenda->judul_rapat,
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Teratai',
                'waktu_mulai' => $startAt->format('Y-m-d H:i:s'),
                'waktu_selesai' => '',
                'is_all_units' => '1',
                'surat_edaran_type' => 'upload',
                'surat_edaran' => $pdf,
            ], [
                'Accept' => 'application/json',
            ]);

        $multipartResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.waktu_selesai', null);

        $agenda->refresh();
        $this->assertNull($agenda->waktu_selesai);
        $this->assertStringContainsString('WIB s.d. Selesai', $agenda->rentang_waktu);
    }

    public function test_api_store_and_update_digital_surat_edaran_lifecycle_and_cleanup(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'administrator')->firstOrFail();
        $startAt = now()->addDays(16)->setTime(9, 0, 0);
        $endAt = $startAt->copy()->addHours(2);

        // 1. Create agenda with surat_edaran_type => 'digital' via API v1
        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', [
                'judul_rapat' => 'Rapat Digital Circular API Awal',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Anggrek',
                'waktu_mulai' => $startAt->format('Y-m-d H:i:s'),
                'waktu_selesai' => $endAt->format('Y-m-d H:i:s'),
                'is_all_units' => true,
                'surat_edaran_type' => 'digital',
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.surat_edaran.type', 'digital')
            ->assertJsonPath('data.surat_edaran.is_digital', true);

        $agenda = Agenda::findOrFail($createResponse->json('data.id'));
        $this->assertSame('digital', $agenda->surat_edaran_type);
        $this->assertNotEmpty($agenda->surat_edaran_config);
        $this->assertNotEmpty($agenda->surat_edaran_path);

        $digitalPath = $agenda->surat_edaran_path;
        Storage::disk('public')->assertExists($digitalPath);
        $initialPdfBytes = Storage::disk('public')->get($digitalPath);

        // 2. Update schedule & title via API v1 while keeping digital mode -> PDF regenerated cleanly
        $newStartAt = $startAt->copy()->addDay()->setTime(10, 30, 0);
        $newEndAt = $newStartAt->copy()->addHours(2);
        $updateResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/agendas/{$agenda->id}", [
                'judul_rapat' => 'Rapat Digital Circular API Revisi Jadwal',
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Melati',
                'waktu_mulai' => $newStartAt->format('Y-m-d H:i:s'),
                'waktu_selesai' => $newEndAt->format('Y-m-d H:i:s'),
                'is_all_units' => true,
                'surat_edaran_type' => 'digital',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.surat_edaran.is_digital', true);

        $agenda->refresh();
        Storage::disk('public')->assertExists($digitalPath);
        $updatedPdfBytes = Storage::disk('public')->get($digitalPath);
        $this->assertNotSame($initialPdfBytes, $updatedPdfBytes);

        // 3. Switch back to 'upload' via multipart PUT -> digital PDF cleaned up and digital config cleared
        $uploadedPdf = UploadedFile::fake()->create('edaran_manual_pengganti.pdf', 120, 'application/pdf');
        $switchResponse = $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/agendas/{$agenda->id}", [
                '_method' => 'PUT',
                'judul_rapat' => $agenda->judul_rapat,
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Melati',
                'waktu_mulai' => $newStartAt->format('Y-m-d H:i:s'),
                'waktu_selesai' => $newEndAt->format('Y-m-d H:i:s'),
                'is_all_units' => '1',
                'surat_edaran_type' => 'upload',
                'surat_edaran' => $uploadedPdf,
            ], [
                'Accept' => 'application/json',
            ]);

        $switchResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.surat_edaran.type', 'upload')
            ->assertJsonPath('data.surat_edaran.is_digital', false);

        $agenda->refresh();
        $this->assertSame('upload', $agenda->surat_edaran_type);
        $this->assertNull($agenda->surat_edaran_config);
        $this->assertNotSame($digitalPath, $agenda->surat_edaran_path);
        Storage::disk('public')->assertMissing($digitalPath);
        Storage::disk('public')->assertExists($agenda->surat_edaran_path);
    }

    public function test_api_participants_addition_and_allow_schedule_overlap_contract(): void
    {
        $admin = User::where('role', 'administrator')->firstOrFail();
        $units = \App\Models\Unit::where('is_active', true)->orderBy('id')->take(2)->get();
        $this->assertCount(2, $units);
        [$unitA, $unitB] = [$units[0], $units[1]];

        $pimpinan = User::create([
            'name' => 'Pimpinan API ' . uniqid(),
            'username' => 'pimp_api_' . uniqid(),
            'nip' => (string) random_int(100000000000000000, 999999999999999999),
            'email' => 'pimp_api_' . uniqid() . '@lldikti10.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'staff',
            'unit_id' => $unitB->id,
            'is_active' => true,
        ]);

        $extraStaff = User::create([
            'name' => 'Extra ASN API ' . uniqid(),
            'username' => 'extra_api_' . uniqid(),
            'nip' => (string) random_int(100000000000000000, 999999999999999999),
            'email' => 'extra_api_' . uniqid() . '@lldikti10.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'staff',
            'unit_id' => $unitB->id,
            'is_active' => true,
        ]);

        $plainStaff = User::create([
            'name' => 'Plain ASN API ' . uniqid(),
            'username' => 'plain_api_' . uniqid(),
            'nip' => (string) random_int(100000000000000000, 999999999999999999),
            'email' => 'plain_api_' . uniqid() . '@lldikti10.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'staff',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        // 1. Concurrent scheduling via API: soft conflict blocked by default, allowed with flag, same room always 422
        $day = now()->addDays(14);
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', [
                'judul_rapat' => 'Rapat API Utama ' . uniqid(),
                'jenis_rapat' => 'koordinasi',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat API 1',
                'waktu_mulai' => $day->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'waktu_selesai' => $day->copy()->setTime(11, 0)->format('Y-m-d H:i:s'),
                'target_type' => 'selected_units',
                'unit_ids' => [$unitA->id],
                'pimpinan_id' => $pimpinan->id,
            ])
            ->assertCreated();

        $overlapPayload = [
            'judul_rapat' => 'Rapat API Paralel ' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat API 2',
            'waktu_mulai' => $day->copy()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'waktu_selesai' => $day->copy()->setTime(12, 0)->format('Y-m-d H:i:s'),
            'target_type' => 'selected_units',
            'unit_ids' => [$unitA->id],
            'pimpinan_id' => $pimpinan->id,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', $overlapPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pimpinan_id']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', array_merge($overlapPayload, ['allow_schedule_overlap' => true]))
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/agendas', array_merge($overlapPayload, [
                'lokasi_ruang' => 'Ruang Rapat API 1',
                'allow_schedule_overlap' => true,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lokasi_ruang']);

        // 2. Ongoing agenda ad-hoc participants via Pimpinan vs forbidden for plain staff
        $ongoing = Agenda::create([
            'created_by' => $admin->id,
            'pimpinan_id' => $pimpinan->id,
            'judul_rapat' => 'Rapat API Adhoc ' . uniqid(),
            'slug' => 'rapat-api-adhoc-' . uniqid(),
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'online',
            'link_meeting' => 'https://zoom.us/j/777888',
            'waktu_mulai' => now()->subMinutes(10),
            'waktu_selesai' => now()->addHours(2),
            'is_all_units' => false,
            'status' => 'ongoing',
        ]);
        $ongoing->units()->sync([$unitA->id]);

        $this->actingAs($pimpinan, 'sanctum')
            ->getJson("/api/v1/agendas/{$ongoing->id}")
            ->assertOk()
            ->assertJsonPath('data.permissions.can_manage_participants', true);

        $this->actingAs($plainStaff, 'sanctum')
            ->getJson("/api/v1/agendas/{$ongoing->id}")
            ->assertOk()
            ->assertJsonPath('data.permissions.can_manage_participants', false);

        $this->actingAs($plainStaff, 'sanctum')
            ->getJson("/api/v1/users/candidates?agenda_id={$ongoing->id}")
            ->assertForbidden();

        $this->actingAs($pimpinan, 'sanctum')
            ->getJson("/api/v1/users/candidates?agenda_id={$ongoing->id}")
            ->assertOk();

        $this->actingAs($plainStaff, 'sanctum')
            ->postJson("/api/v1/agendas/{$ongoing->id}/participants", ['user_ids' => [$extraStaff->id]])
            ->assertForbidden();

        $addResp = $this->actingAs($pimpinan, 'sanctum')
            ->postJson("/api/v1/agendas/{$ongoing->id}/participants", [
                'user_ids' => [$extraStaff->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $invitedIds = collect($addResp->json('data.invited_users'))->pluck('id')->all();
        $this->assertContains($extraStaff->id, $invitedIds);
        $this->assertTrue($ongoing->fresh()->isUserEligible($extraStaff));

        $unattendedResp = $this->actingAs($pimpinan, 'sanctum')
            ->getJson("/api/v1/agendas/{$ongoing->id}/attendances?status=unattended&per_page=100")
            ->assertOk();
        $unattendedIds = collect($unattendedResp->json('data'))->pluck('user_id')->all();
        $this->assertContains($extraStaff->id, $unattendedIds);
    }
}



