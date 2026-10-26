<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    protected function tearDown(): void
    {
        // Pastikan aplikasi selalu kembali live setelah pengujian
        Artisan::call('up');
        parent::tearDown();
    }

    /**
     * Memvalidasi bahwa halaman errors/503.blade.php merender dengan tepat
     * saat aplikasi berada dalam status pemeliharaan.
     */
    public function test_maintenance_mode_renders_govtech_503_page(): void
    {
        Artisan::call('down', [
            '--render' => 'errors.503',
            '--status' => 503,
        ]);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertSee('Layanan Sedang Dalam Pemeliharaan Berkala');
        $response->assertSee('PEMELIHARAAN SISTEM (503)');
        $response->assertSee('Periksa Ketersediaan Layanan');
        $response->assertSee('LLDIKTI');

        // Bawa kembali ke mode live
        Artisan::call('up');

        // Setelah up, rute root kembali mengembalikan 302 redirect ke login
        $responseAfterUp = $this->get('/');
        $responseAfterUp->assertStatus(302);
    }

    /**
     * Memvalidasi bahwa endpoint /up dapat diakses sebagai health check.
     */
    public function test_health_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
    }
}
