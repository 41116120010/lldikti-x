<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfiePreviewParityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $staff;
    protected Agenda $agenda;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::where('role', 'administrator')->first()
            ?? User::factory()->create(['role' => 'administrator']);

        $unit = Unit::first() ?? Unit::factory()->create();

        $this->staff = User::factory()->create([
            'username' => 'budisantoso_' . uniqid(),
            'role' => 'staff',
            'unit_id' => $unit->id,
            'name' => 'Budi Santoso, S.Kom.',
            'nip' => '199001012015011001',
        ]);

        $this->agenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Koordinasi Evaluasi Presensi Digital',
            'slug' => 'rapat-koordinasi-evaluasi-presensi-digital',
            'jenis_rapat' => 'internal',
            'tipe_rapat' => 'offline',
            'lokasi_ruang' => 'Ruang Sidang Utama LLDIKTI Wilayah X',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(2),
            'status' => 'ongoing',
            'is_all_units' => true,
            'nama_pimpinan' => 'Dr. Pimpinan Rapat',
            'nip_pimpinan' => '197001011995011001',
            'nama_notulis' => 'Notulis Resmi',
            'nip_notulis' => '198501012010012001',
        ]);

        $dummyImage = UploadedFile::fake()->image('selfie.jpg', 600, 800);
        $selfiePath = $dummyImage->store('attendances/selfies', 'public');

        $dummySig = UploadedFile::fake()->image('signature.png', 300, 100);
        $sigPath = $dummySig->store('attendances/signatures', 'public');

        $this->attendance = Attendance::create([
            'agenda_id' => $this->agenda->id,
            'user_id' => $this->staff->id,
            'signed_at' => now(),
            'selfie_path' => $selfiePath,
            'signature_path' => $sigPath,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
    }

    public function test_attendance_model_provides_selfie_url_accessor(): void
    {
        $this->assertNotNull($this->attendance->selfie_url);
        $this->assertStringContainsString('/storage/attendances/selfies/', $this->attendance->selfie_url);
        $this->assertStringEndsWith('.jpg', $this->attendance->selfie_url);
        $this->assertNotNull($this->attendance->signature_url);
    }

    public function test_report_show_renders_selfie_preview_trigger_and_modal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.show', $this->agenda));

        $response->assertStatus(200);
        $response->assertSee('data-selfie-modal-trigger', false);
        $response->assertSee('id="selfie-preview-modal"', false);
        $response->assertSee('id="selfie-modal-image"', false);
        $response->assertSee('window.selfiePreviewModal', false);
        $response->assertSee('Verifikasi Foto Selfie Kehadiran', false);
    }

    public function test_attendance_history_renders_selfie_preview_trigger_and_modal(): void
    {
        $response = $this->actingAs($this->staff)->get(route('attendances.history'));

        $response->assertStatus(200);
        $response->assertSee('data-selfie-modal-trigger', false);
        $response->assertSee('id="selfie-preview-modal"', false);
        $response->assertSee('id="selfie-modal-image"', false);
        $response->assertSee('window.selfiePreviewModal', false);
    }

    public function test_agenda_admin_show_renders_selfie_thumbnail_in_attendee_sidebar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agendas.show', $this->agenda));

        $response->assertStatus(200);
        $response->assertSee('data-selfie-modal-trigger', false);
        $response->assertSee('id="selfie-preview-modal"', false);
        $response->assertSee('id="selfie-modal-viewport"', false);
    }

    public function test_attendance_success_receipt_renders_interactive_selfie_modal_trigger(): void
    {
        $response = $this->actingAs($this->staff)->get(route('attendances.success', [$this->agenda, $this->attendance]));

        $response->assertStatus(200);
        $response->assertSee('data-selfie-modal-trigger', false);
        $response->assertSee('id="selfie-preview-modal"', false);
        $response->assertSee('Klik perbesar', false);
    }

    public function test_notulen_workstation_renders_interactive_selfie_in_attendance_table(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agendas.notulen', $this->agenda));

        $response->assertStatus(200);
        $response->assertSee('data-selfie-modal-trigger', false);
        $response->assertSee('id="selfie-preview-modal"', false);
    }

    public function test_staff_agenda_show_renders_verified_selfie_thumbnail(): void
    {
        $response = $this->actingAs($this->staff)->get(route('agendas.show', $this->agenda));

        $response->assertStatus(200);
        $response->assertSee('Presensi Sah Terverifikasi', false);
        $response->assertSee('data-selfie-modal-trigger', false);
        $response->assertSee('id="selfie-preview-modal"', false);
    }

    public function test_attendance_create_form_renders_inspection_controls_and_modal(): void
    {
        // Create another agenda without attendance yet
        $freshAgenda = Agenda::create([
            'created_by' => $this->admin->id,
            'judul_rapat' => 'Rapat Uji Form Presensi',
            'slug' => 'rapat-uji-form-presensi',
            'jenis_rapat' => 'internal',
            'tipe_rapat' => 'offline',
            'waktu_mulai' => now()->subMinute(),
            'waktu_selesai' => now()->addHour(),
            'status' => 'ongoing',
            'is_all_units' => true,
        ]);

        $response = $this->actingAs($this->staff)->get(route('attendances.create', $freshAgenda));

        $response->assertStatus(200);
        $response->assertSee('id="btn-inspect-selfie"', false);
        $response->assertSee('id="selfie-actions-after-capture"', false);
        $response->assertSee('id="selfie-preview-modal"', false);
    }

    public function test_modal_component_contains_zoom_rotate_and_metadata_elements(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.show', $this->agenda));

        $response->assertStatus(200);
        $response->assertSee('id="selfie-modal-zoom-val"', false);
        $response->assertSee('window.selfiePreviewModal.zoomIn()', false);
        $response->assertSee('window.selfiePreviewModal.zoomOut()', false);
        $response->assertSee('window.selfiePreviewModal.rotate()', false);
        $response->assertSee('window.selfiePreviewModal.reset()', false);
        $response->assertSee('id="selfie-modal-user-name"', false);
        $response->assertSee('id="selfie-modal-user-nip"', false);
        $response->assertSee('id="selfie-modal-unit-name"', false);
        $response->assertSee('id="selfie-modal-signed-at"', false);
        $response->assertSee('id="selfie-modal-ip-address"', false);
    }
}
