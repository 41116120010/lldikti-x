<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ModalAutoDismissTest extends TestCase
{
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superadmin = User::where('username', 'superadmin')->first();
    }

    public function test_authenticated_layout_contains_modal_timer_track_and_bar(): void
    {
        $response = $this->actingAs($this->superadmin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="app-modal"', false);
        $response->assertSee('id="modal-timer-track"', false);
        $response->assertSee('id="modal-timer-bar"', false);
        $response->assertSee('modal-timer-track hidden', false);
    }

    public function test_guest_layout_contains_modal_timer_track_and_bar(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('id="app-modal"', false);
        $response->assertSee('id="modal-timer-track"', false);
        $response->assertSee('id="modal-timer-bar"', false);
    }

    public function test_success_flash_message_includes_auto_close_attribute(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['success' => 'Agenda rapat berhasil dijadwalkan secara resmi.'])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="flash-modal-data"', false);
        $response->assertSee('data-type="success"', false);
        $response->assertSee('data-auto-close="true"', false);
        $response->assertSee('Agenda rapat berhasil dijadwalkan secara resmi.', false);
    }

    public function test_error_flash_message_includes_auto_close_attribute(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['error' => 'Gagal memproses berkas dokumentasi karena gangguan koneksi.'])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="flash-modal-data"', false);
        $response->assertSee('data-type="error"', false);
        $response->assertSee('data-auto-close="true"', false);
        $response->assertSee('Gagal memproses berkas dokumentasi', false);
    }

    public function test_warning_flash_message_includes_auto_close_attribute(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['warning' => 'Waktu pelaksanaan rapat tersisa 15 menit lagi.'])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="flash-modal-data"', false);
        $response->assertSee('data-type="warning"', false);
        $response->assertSee('data-auto-close="true"', false);
        $response->assertSee('Waktu pelaksanaan rapat tersisa 15 menit lagi.', false);
    }

    public function test_validation_errors_in_view_render_auto_close_flash_data(): void
    {
        Auth::login($this->superadmin);
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag(['judul_rapat' => ['Judul rapat wajib diisi.']]));

        $view = $this->view('layouts.app', [
            'errors' => $viewErrorBag,
        ]);

        $view->assertSee('id="flash-modal-data"', false);
        $view->assertSee('data-type="warning"', false);
        $view->assertSee('data-auto-close="true"', false);
        $view->assertSee('Judul rapat wajib diisi.', false);
    }

    public function test_guest_layout_validation_errors_render_auto_close_flash_data(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag(['login' => ['Kredensial tidak valid.']]));

        $view = $this->view('layouts.guest', [
            'errors' => $viewErrorBag,
        ]);

        $view->assertSee('id="flash-modal-data"', false);
        $view->assertSee('data-type="warning"', false);
        $view->assertSee('data-auto-close="true"', false);
        $view->assertSee('Kredensial tidak valid.', false);
    }

    public function test_guest_layout_flash_warning_renders_auto_close_flash_data(): void
    {
        $response = $this->withSession(['warning' => 'Sesi login Anda telah kedaluwarsa. Silakan masuk kembali.'])
            ->get('/login');

        $response->assertOk();
        $response->assertSee('id="flash-modal-data"', false);
        $response->assertSee('data-type="warning"', false);
        $response->assertSee('data-auto-close="true"', false);
        $response->assertSee('Sesi login Anda telah kedaluwarsa.', false);
    }

    public function test_guest_layout_flash_success_renders_auto_close_flash_data(): void
    {
        $response = $this->withSession(['success' => 'Kata sandi berhasil diperbarui.'])
            ->get('/login');

        $response->assertOk();
        $response->assertSee('id="flash-modal-data"', false);
        $response->assertSee('data-type="success"', false);
        $response->assertSee('data-auto-close="true"', false);
        $response->assertSee('Kata sandi berhasil diperbarui.', false);
    }
}
