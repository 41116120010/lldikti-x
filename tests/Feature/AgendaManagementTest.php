<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaManagementTest extends TestCase
{
    public function test_authenticated_users_can_view_agendas_index(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();

        $this->actingAs($superadmin)->get('/admin/agendas')->assertStatus(200)->assertSee('Agenda Rapat Kedinasan');
        $this->actingAs($staff)->get('/admin/agendas')->assertStatus(200);
    }

    public function test_administrator_can_create_universal_agenda_with_circular_file(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();
        $file = UploadedFile::fake()->create('surat_undangan.pdf', 1024, 'application/pdf');

        $uniqueTitle = 'Rapat Pleno Koordinasi ' . uniqid();

        $response = $this->actingAs($superadmin)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'pleno',
            'tipe_rapat' => 'hybrid',
            'lokasi_ruang' => 'Auditorium Utama Lantai 3',
            'link_meeting' => 'https://zoom.us/j/987654321',
            'waktu_mulai' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDays(2)->addHours(3)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
            'surat_edaran' => $file,
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('agendas', [
            'judul_rapat' => $uniqueTitle,
            'is_all_units' => true,
            'tipe_rapat' => 'hybrid',
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda->surat_edaran_path);
        Storage::disk('public')->assertExists($agenda->surat_edaran_path);

        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'CREATE_AGENDA',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_unit_can_create_scoped_agenda(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->first();
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();

        $uniqueTitle = 'Rapat Koordinasi Bersama ' . uniqid();

        $response = $this->actingAs($adminAkm)->post('/admin/agendas', [
            'judul_rapat' => $uniqueTitle,
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Rapat Pokja AKM',
            'waktu_mulai' => now()->addDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'is_all_units' => false,
            'unit_ids' => [$unitAkm->id, $unitKlb->id],
            'status' => 'scheduled',
        ]);

        $agenda = Agenda::where('judul_rapat', $uniqueTitle)->first();
        $this->assertNotNull($agenda);
        $this->assertCount(2, $agenda->units);
    }

    public function test_staff_cannot_create_agenda(): void
    {
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($staff)->post('/admin/agendas', [
            'judul_rapat' => 'Rapat Ilegal',
            'jenis_rapat' => 'koordinasi',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang 1',
            'waktu_mulai' => now()->addDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'is_all_units' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_agenda_status_transition_works(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->patch("/admin/agendas/{$agenda->id}/status", [
            'status' => 'ongoing',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'status' => 'ongoing',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'UPDATE_AGENDA_STATUS',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_update_notulensi_and_upload_documentation_photos(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $photo1 = UploadedFile::fake()->create('foto_suasana_1.jpg', 500, 'image/jpeg');
        $photo2 = UploadedFile::fake()->create('foto_suasana_2.webp', 500, 'image/webp');

        $response = $this->actingAs($superadmin)->put("/admin/agendas/{$agenda->id}/notulen", [
            'notulensi' => "1. Pembukaan oleh Kepala Lembaga.\n2. Pembahasan evaluasi pelaporan semester.",
            'kesimpulan' => "Seluruh PTS wajib merampungkan sinkronisasi data selambatnya akhir bulan ini.",
            'photos' => [$photo1, $photo2],
            'captions' => ['Suasana Pembukaan Rapat', 'Pemaparan Materi Pokja'],
        ]);

        $response->assertRedirect("/admin/agendas/{$agenda->id}");

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'notulensi' => "1. Pembukaan oleh Kepala Lembaga.\n2. Pembahasan evaluasi pelaporan semester.",
        ]);

        $this->assertDatabaseHas('agenda_documentations', [
            'agenda_id' => $agenda->id,
            'caption' => 'Suasana Pembukaan Rapat',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'UPDATE_AGENDA_MINUTES',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_delete_photo_documentation(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $doc = AgendaDocumentation::where('agenda_id', $agenda->id)->first();

        if (!$doc) {
            $doc = AgendaDocumentation::create([
                'agenda_id' => $agenda->id,
                'file_path' => 'documentations/sample.jpg',
                'caption' => 'Sample Doc',
            ]);
        }

        $response = $this->actingAs($superadmin)->delete("/admin/agendas/{$agenda->id}/documentations/{$doc->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('agenda_documentations', ['id' => $doc->id]);
    }

    public function test_admin_unit_can_access_create_agenda_page(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();

        $response = $this->actingAs($adminAkm)->get(route('admin.agendas.create'));

        $response->assertStatus(200);
        $response->assertSee('Buat Agenda Rapat Baru');
        $response->assertSee($adminAkm->unit->nama_unit);
    }

    public function test_admin_unit_can_access_edit_agenda_page(): void
    {
        $adminAkm = User::where('username', 'admin_akademik')->first();
        $agenda = Agenda::where('created_by', $adminAkm->id)->first();

        if (!$agenda) {
            $agenda = Agenda::create([
                'created_by' => $adminAkm->id,
                'judul_rapat' => 'Rapat Internal Bagian Akademik',
                'slug' => 'rapat-internal-akademik-' . uniqid(),
                'jenis_rapat' => 'internal',
                'tipe_rapat' => 'offline',
                'lokasi_ruang' => 'Ruang Rapat Pokja AKM',
                'waktu_mulai' => now()->addDay(),
                'waktu_selesai' => now()->addDay()->addHours(2),
                'is_all_units' => false,
                'status' => 'scheduled',
            ]);
            $agenda->units()->sync([$adminAkm->unit_id]);
        }

        $response = $this->actingAs($adminAkm)->get(route('admin.agendas.edit', $agenda));

        $response->assertStatus(200);
        $response->assertSee('Edit Agenda Rapat');
        $response->assertSee($agenda->judul_rapat);
    }
}
