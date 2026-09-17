<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgendaDeletionTest extends TestCase
{
    public function test_administrator_can_delete_any_agenda_across_all_units(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unit = Unit::first() ?? Unit::create(['nama_unit' => 'Unit Akademik', 'kode_unit' => 'AKAD']);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Uji Hapus Superadmin',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang 1',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($superadmin)->delete("/admin/agendas/{$agenda->id}");

        $response->assertRedirect('/admin/agendas');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('agendas', ['id' => $agenda->id]);
    }

    public function test_unit_admin_can_delete_own_created_agenda(): void
    {
        $unitA = Unit::create(['nama_unit' => 'Unit Kerja A', 'kode_unit' => 'UKA']);
        $adminA = User::create([
            'name' => 'Admin Unit A',
            'nip' => '198001012010011011',
            'username' => 'admin_unit_a',
            'email' => 'admin_a@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Internal Unit A',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang UKA',
            'status' => 'scheduled',
            'created_by' => $adminA->id,
            'is_all_units' => false,
        ]);
        $agenda->units()->sync([$unitA->id]);

        $response = $this->actingAs($adminA)->delete("/admin/agendas/{$agenda->id}");

        $response->assertRedirect('/admin/agendas');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('agendas', ['id' => $agenda->id]);

        $adminA->delete();
        $unitA->delete();
    }

    public function test_unit_admin_can_delete_peer_agenda_from_same_unit(): void
    {
        $unitA = Unit::create(['nama_unit' => 'Unit Kerja B', 'kode_unit' => 'UKB']);
        $admin1 = User::create([
            'name' => 'Admin B1',
            'nip' => '198101012010011012',
            'username' => 'admin_b1',
            'email' => 'admin_b1@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        $admin2 = User::create([
            'name' => 'Admin B2',
            'nip' => '198201012010011013',
            'username' => 'admin_b2',
            'email' => 'admin_b2@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Buatan Rekan Unit B1',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang UKB',
            'status' => 'scheduled',
            'created_by' => $admin1->id,
            'is_all_units' => false,
        ]);
        $agenda->units()->sync([$unitA->id]);

        // Admin2 dari unit yang sama menghapus agenda buatan Admin1
        $response = $this->actingAs($admin2)->delete("/admin/agendas/{$agenda->id}");

        $response->assertRedirect('/admin/agendas');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('agendas', ['id' => $agenda->id]);

        $admin1->delete();
        $admin2->delete();
        $unitA->delete();
    }

    public function test_unit_admin_can_delete_assigned_non_universal_agenda_for_their_unit(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unitA = Unit::create(['nama_unit' => 'Unit Kerja C', 'kode_unit' => 'UKC']);
        $adminC = User::create([
            'name' => 'Admin C',
            'nip' => '198301012010011014',
            'username' => 'admin_c',
            'email' => 'admin_c@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        // Agenda non-universal yang dibuat khusus ditujukan untuk Unit C
        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Terbatas Khusus Unit C',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang UKC',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => false,
        ]);
        $agenda->units()->sync([$unitA->id]);

        $response = $this->actingAs($adminC)->delete("/admin/agendas/{$agenda->id}");

        $response->assertRedirect('/admin/agendas');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('agendas', ['id' => $agenda->id]);

        $adminC->delete();
        $unitA->delete();
    }

    public function test_foreign_unit_admin_cannot_delete_agenda_of_another_unit(): void
    {
        $unitA = Unit::create(['nama_unit' => 'Unit Kerja D', 'kode_unit' => 'UKD']);
        $unitB = Unit::create(['nama_unit' => 'Unit Kerja E', 'kode_unit' => 'UKE']);

        $adminD = User::create([
            'name' => 'Admin D',
            'nip' => '198401012010011015',
            'username' => 'admin_d',
            'email' => 'admin_d@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        $adminE = User::create([
            'name' => 'Admin E',
            'nip' => '198501012010011016',
            'username' => 'admin_e',
            'email' => 'admin_e@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitB->id,
            'is_active' => true,
        ]);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Milik Unit D',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang UKD',
            'status' => 'scheduled',
            'created_by' => $adminD->id,
            'is_all_units' => false,
        ]);
        $agenda->units()->sync([$unitA->id]);

        // Admin E dari unit lain mencoba menghapus agenda Unit D
        $response = $this->actingAs($adminE)->delete("/admin/agendas/{$agenda->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('agendas', ['id' => $agenda->id]);

        $agenda->delete();
        $adminD->delete();
        $adminE->delete();
        $unitA->delete();
        $unitB->delete();
    }

    public function test_unit_admin_cannot_delete_universal_agenda_created_by_superadmin(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unit = Unit::first() ?? Unit::create(['nama_unit' => 'Unit Uji', 'kode_unit' => 'UU']);

        $adminUnit = User::create([
            'name' => 'Admin Unit Biasa',
            'nip' => '198601012010011017',
            'username' => 'admin_biasa',
            'email' => 'admin_biasa@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Pleno Universal Seluruh Unit',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Aula Utama',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($adminUnit)->delete("/admin/agendas/{$agenda->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('agendas', ['id' => $agenda->id]);

        $agenda->delete();
        $adminUnit->delete();
    }

    public function test_staff_cannot_delete_agenda(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $staff = User::where('role', 'staff')->first();

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Uji Coba Staf',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang Staf',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($staff)->delete("/admin/agendas/{$agenda->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('agendas', ['id' => $agenda->id]);

        $agenda->delete();
    }

    public function test_agenda_deletion_cleans_up_all_physical_files_from_storage(): void
    {
        Storage::fake('public');
        $superadmin = User::where('role', 'administrator')->first();

        // 1. Uploaded files
        $fakeSurat = UploadedFile::fake()->create('surat_edaran.pdf', 100, 'application/pdf');
        $suratPath = $fakeSurat->store('surat_edaran', 'public');

        $fakeLogo = UploadedFile::fake()->image('custom_logo.png', 100, 100);
        $logoPath = $fakeLogo->store('logos', 'public');

        $agenda = Agenda::create([
            'judul_rapat' => 'Rapat Lengkap Dengan Berkas',
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang Digital',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
            'surat_edaran_path' => $suratPath,
            'report_config' => [
                'custom_logo_path' => $logoPath,
            ],
        ]);

        // Add documentation
        $fakeDoc = UploadedFile::fake()->image('dokumentasi.jpg', 300, 300);
        $docPath = $fakeDoc->store("documentations/{$agenda->id}", 'public');
        $doc = AgendaDocumentation::create([
            'agenda_id' => $agenda->id,
            'file_path' => $docPath,
            'caption' => 'Foto Rapat',
            'sort_order' => 1,
        ]);

        // Verify all files exist before deletion
        Storage::disk('public')->assertExists($suratPath);
        Storage::disk('public')->assertExists($logoPath);
        Storage::disk('public')->assertExists($docPath);

        // Perform agenda deletion
        $response = $this->actingAs($superadmin)->delete("/admin/agendas/{$agenda->id}");

        $response->assertRedirect('/admin/agendas');
        $response->assertSessionHas('success');

        // Verify database records deleted
        $this->assertDatabaseMissing('agendas', ['id' => $agenda->id]);
        $this->assertDatabaseMissing('agenda_documentations', ['id' => $doc->id]);

        // Verify storage files cleaned up (Zero-Orphan Files)
        Storage::disk('public')->assertMissing($suratPath);
        Storage::disk('public')->assertMissing($logoPath);
        Storage::disk('public')->assertMissing($docPath);
    }

    public function test_detail_agenda_does_not_contain_direktur_signature_column_and_shows_delete_button_for_authorized_user(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agendaWithoutAttendances = Agenda::create([
            'judul_rapat' => 'Rapat Uji Detail Hapus ' . uniqid(),
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHour(),
            'waktu_selesai' => now()->addHours(2),
            'lokasi_ruang' => 'Ruang Uji',
            'status' => 'scheduled',
            'created_by' => $superadmin->id,
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaWithoutAttendances->id}");

        $response->assertStatus(200);
        // Signature headers
        $response->assertSee('Pemimpin Rapat');
        $response->assertSee('Notulis Rapat');
        // Redundant Direktur signature column must NOT be present
        $response->assertDontSee('Direktur / Penanggung Jawab');
        $response->assertDontSee('Direktur / Pimpinan Unit');

        // Delete button must be present for superadmin on agenda without attendances
        $response->assertSee('Hapus Agenda');

        $agendaWithoutAttendances->delete();
    }

    public function test_agendas_index_and_detail_hides_delete_button_when_agenda_has_attendances(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $unitA = Unit::create(['nama_unit' => 'Unit Uji Presensi Hapus', 'kode_unit' => 'UUPH_' . uniqid()]);
        $adminUnit = User::create([
            'name' => 'Admin Unit UUPH',
            'nip' => '198701012010011088',
            'username' => 'admin_uuph_' . uniqid(),
            'email' => 'admin_uuph_' . uniqid() . '@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        // Agenda A: Has attendances
        $agendaWithAtt = Agenda::create([
            'judul_rapat' => 'Rapat Berisi Presensi ' . uniqid(),
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->addHour(),
            'lokasi_ruang' => 'Ruang A',
            'status' => 'ongoing',
            'created_by' => $adminUnit->id,
            'is_all_units' => false,
        ]);
        $agendaWithAtt->units()->sync([$unitA->id]);

        $attUser = User::create([
            'name' => 'Peserta Uji Presensi',
            'nip' => '198801012010011089',
            'username' => 'peserta_uuph_' . uniqid(),
            'email' => 'peserta_uuph_' . uniqid() . '@lldikti.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'unit_id' => $unitA->id,
            'is_active' => true,
        ]);

        $attendance = Attendance::create([
            'agenda_id' => $agendaWithAtt->id,
            'user_id' => $attUser->id,
            'signed_at' => now(),
            'selfie_path' => 'selfies/test.jpg',
            'signature_path' => 'signatures/test.png',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
        ]);

        // Agenda B: No attendances
        $agendaWithoutAtt = Agenda::create([
            'judul_rapat' => 'Rapat Tanpa Presensi ' . uniqid(),
            'tanggal_rapat' => now()->toDateString(),
            'waktu_mulai' => now()->addHours(2),
            'waktu_selesai' => now()->addHours(4),
            'lokasi_ruang' => 'Ruang B',
            'status' => 'scheduled',
            'created_by' => $adminUnit->id,
            'is_all_units' => false,
        ]);
        $agendaWithoutAtt->units()->sync([$unitA->id]);

        try {
            // 1. SUPERADMIN on index (admin/agendas)
            $superIndexWith = $this->actingAs($superadmin)->get('/admin/agendas?search=' . urlencode($agendaWithAtt->judul_rapat));
            $superIndexWith->assertStatus(200);
            $superIndexWith->assertSee($agendaWithAtt->judul_rapat);
            $superIndexWith->assertDontSee('title="Hapus Agenda"', false);

            $superIndexWithout = $this->actingAs($superadmin)->get('/admin/agendas?search=' . urlencode($agendaWithoutAtt->judul_rapat));
            $superIndexWithout->assertStatus(200);
            $superIndexWithout->assertSee($agendaWithoutAtt->judul_rapat);
            $superIndexWithout->assertSee('title="Hapus Agenda"', false);

            // 2. SUPERADMIN on detail (admin/agendas/{id})
            $superDetailWith = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaWithAtt->id}");
            $superDetailWith->assertStatus(200);
            $superDetailWith->assertDontSee('Hapus Agenda');

            $superDetailWithout = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaWithoutAtt->id}");
            $superDetailWithout->assertStatus(200);
            $superDetailWithout->assertSee('Hapus Agenda');

            // 3. SUPERADMIN on edit (admin/agendas/{id}/edit)
            $superEditWith = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaWithAtt->id}/edit");
            $superEditWith->assertStatus(200);
            $superEditWith->assertDontSee('Zona Bahaya');
            $superEditWith->assertDontSee('Hapus Agenda Ini');

            $superEditWithout = $this->actingAs($superadmin)->get("/admin/agendas/{$agendaWithoutAtt->id}/edit");
            $superEditWithout->assertStatus(200);
            $superEditWithout->assertSee('Zona Bahaya');
            $superEditWithout->assertSee('Hapus Agenda Ini');

            // 4. ADMIN UNIT on index (admin/agendas)
            $unitIndexWith = $this->actingAs($adminUnit)->get('/admin/agendas?search=' . urlencode($agendaWithAtt->judul_rapat));
            $unitIndexWith->assertStatus(200);
            $unitIndexWith->assertSee($agendaWithAtt->judul_rapat);
            $unitIndexWith->assertDontSee('title="Hapus Agenda"', false);

            $unitIndexWithout = $this->actingAs($adminUnit)->get('/admin/agendas?search=' . urlencode($agendaWithoutAtt->judul_rapat));
            $unitIndexWithout->assertStatus(200);
            $unitIndexWithout->assertSee($agendaWithoutAtt->judul_rapat);
            $unitIndexWithout->assertSee('title="Hapus Agenda"', false);

            // 5. ADMIN UNIT on detail (admin/agendas/{id})
            $unitDetailWith = $this->actingAs($adminUnit)->get("/admin/agendas/{$agendaWithAtt->id}");
            $unitDetailWith->assertStatus(200);
            $unitDetailWith->assertDontSee('Hapus Agenda');

            $unitDetailWithout = $this->actingAs($adminUnit)->get("/admin/agendas/{$agendaWithoutAtt->id}");
            $unitDetailWithout->assertStatus(200);
            $unitDetailWithout->assertSee('Hapus Agenda');
        } finally {
            $attendance->delete();
            $agendaWithAtt->delete();
            $agendaWithoutAtt->delete();
            $attUser->delete();
            $adminUnit->delete();
            $unitA->delete();
        }
    }

    public function test_report_detail_does_not_contain_direktur_signature_column(): void
    {
        $superadmin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertSee('Pemimpin Rapat');
        $response->assertSee('Notulis Rapat');
        $response->assertDontSee('Direktur / Penanggung Jawab');
        $response->assertDontSee('Direktur / Pimpinan Unit');
    }
}
