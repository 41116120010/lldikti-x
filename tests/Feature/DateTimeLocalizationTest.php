<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class DateTimeLocalizationTest extends TestCase
{
    public function test_application_timezone_and_locale_are_properly_configured(): void
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
        $this->assertEquals('id', config('app.locale'));
        $this->assertEquals('Asia/Jakarta', date_default_timezone_get());
        $this->assertEquals('id', Carbon::getLocale());
    }

    public function test_carbon_translates_day_and_month_names_to_indonesian(): void
    {
        // 2026-09-03 is Thursday / Kamis
        $date = Carbon::parse('2026-09-03 10:00:00', 'Asia/Jakarta');
        
        $translated = $date->translatedFormat('l, d F Y');
        $this->assertEquals('Kamis, 03 September 2026', $translated);

        // Monday / Senin
        $monday = Carbon::parse('2026-09-07 09:00:00', 'Asia/Jakarta');
        $this->assertEquals('Senin, 07 September 2026', $monday->translatedFormat('l, d F Y'));
    }

    public function test_agenda_show_renders_clean_date_and_wib_without_corruption(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($admin)->get("/admin/agendas/{$agenda->id}");
        $response->assertStatus(200);

        // Must contain WIB
        $response->assertSee('WIB');

        // Must NOT contain corruption from unescaped &bull; in format strings (&b[microseconds][day][day];)
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/&b\d+/', $content);
        $this->assertStringNotContainsString('KamisKamis', $content);
        $this->assertStringNotContainsString('ThursdayThursday', $content);
        $this->assertStringNotContainsString('SeninSenin', $content);
    }

    public function test_staff_agenda_show_renders_clean_date_and_wib_without_corruption(): void
    {
        $staff = User::where('role', 'staff')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($staff)->get("/agendas/{$agenda->id}");
        $response->assertStatus(200);

        // Must contain WIB
        $response->assertSee('WIB');

        // Must NOT contain corruption from unescaped &bull;
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/&b\d+/', $content);
        $this->assertStringNotContainsString('KamisKamis', $content);
        $this->assertStringNotContainsString('ThursdayThursday', $content);
    }

    public function test_attendance_receipt_renders_clean_date_and_wib_without_corruption(): void
    {
        $staff = User::where('role', 'staff')->first();
        $attendance = Attendance::where('user_id', $staff->id)->first();

        if (!$attendance) {
            $agenda = Agenda::first();
            $attendance = Attendance::create([
                'agenda_id' => $agenda->id,
                'user_id' => $staff->id,
                'selfie_path' => 'selfies/test.jpg',
                'signature_path' => 'signatures/test.png',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit Test',
                'signed_at' => now(),
            ]);
        }

        $response = $this->actingAs($staff)->get(route('attendances.success', [$attendance->agenda_id, $attendance->id]));
        $response->assertStatus(200);

        // Must contain WIB
        $response->assertSee('WIB');

        // Must NOT contain corrupted &bull;
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/&b\d+/', $content);
        $this->assertStringNotContainsString('KamisKamis', $content);
        $this->assertStringNotContainsString('ThursdayThursday', $content);
    }
}
