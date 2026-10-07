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
        $this->assertCount(2, $agenda->documentations); // 1 from fixture + 1 from upload
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
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }
}


