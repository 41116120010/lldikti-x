<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BrandIdentityIconTest extends TestCase
{
    public function test_sidebar_displays_tut_wuri_handayani_logo_and_not_user_svg(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);

        // Assert Tut Wuri Handayani logo image is rendered
        $response->assertSee('images/tut-wuri-handayani.png');
        $response->assertSee('alt="Logo Tut Wuri Handayani"', false);

        // Assert favicon links to Tut Wuri Handayani
        $response->assertSee('<link rel="icon" type="image/png" href="http://localhost:8001/images/tut-wuri-handayani.png">', false);
        $response->assertSee('<link rel="apple-touch-icon" href="http://localhost:8001/images/tut-wuri-handayani.png">', false);

        // Assert user SVG silhouette is NOT used in brand mark
        $content = $response->getContent();
        $this->assertStringNotContainsString("href=\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'", $content);
    }

    public function test_login_page_displays_tut_wuri_handayani_in_desktop_and_mobile_brand(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Desktop hero has Tut Wuri logo
        $response->assertSee('images/tut-wuri-handayani.png');
        $response->assertSee('alt="Logo Tut Wuri Handayani"', false);

        // Mobile brand has Tut Wuri logo
        $response->assertSee('class="auth-brand md:hidden"', false);

        // Favicon links to Tut Wuri Handayani
        $response->assertSee('<link rel="icon" type="image/png" href="http://localhost:8001/images/tut-wuri-handayani.png">', false);
    }

    public function test_favicon_ico_exists_and_is_not_empty(): void
    {
        $faviconPath = public_path('favicon.ico');
        $this->assertFileExists($faviconPath);
        $this->assertGreaterThan(500, filesize($faviconPath));
    }
}
