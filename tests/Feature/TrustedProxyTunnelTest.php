<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;

class TrustedProxyTunnelTest extends TestCase
{
    public function test_local_cloudflare_tunnel_proxy_headers_generate_https_urls(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
        ])->withHeaders([
            'Host' => 'demo-uji.trycloudflare.com',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'demo-uji.trycloudflare.com',
        ])->get('/login');

        $response->assertStatus(200);
        $response->assertSee('action="https://demo-uji.trycloudflare.com/login"', false);
        $response->assertDontSee('http://demo-uji.trycloudflare.com', false);
    }

    public function test_local_direct_access_without_forwarded_headers_stays_http(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
        ])->get('http://127.0.0.1:8000/login');

        $response->assertStatus(200);
        $response->assertSee('action="http://127.0.0.1:8000/login"', false);
    }

    public function test_untrusted_external_ip_cannot_spoof_forwarded_proto(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.45',
        ])->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('http://127.0.0.1:8000/login');

        $response->assertStatus(200);
        $response->assertSee('action="http://127.0.0.1:8000/login"', false);
        $response->assertDontSee('https://127.0.0.1:8000', false);
    }
}
