<?php

namespace Tests\Feature\Api\V1;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardAndReportApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_access_dashboard_summary(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'stats' => [
                        'total_agendas',
                        'ongoing_agendas',
                        'completed_agendas',
                        'upcoming_agendas',
                    ],
                    'active_agendas',
                    'recent_attendances',
                ],
            ]);
    }

    public function test_user_can_access_report_summary(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/reports/summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_agendas',
                    'completed_agendas',
                    'ongoing_agendas',
                    'total_attendances',
                    'avg_attendances_per_agenda',
                ],
            ]);
    }

    public function test_user_can_access_agenda_recap(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/reports/agendas/{$agenda->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'agenda',
                    'export_links' => [
                        'pdf',
                        'word',
                    ],
                ],
            ]);
    }

    public function test_user_can_view_profile_and_logs(): void
    {
        $staff = User::where('role', 'staff')->first();
        $this->assertNotNull($staff);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $staff->id);

        $logsResponse = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/profile/logs');

        $logsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_user_can_export_summary_csv_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/v1/reports/summary/csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="Rekapitulasi_Agenda_LLDIKTI_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Judul Rapat', $content);
        $this->assertStringContainsString('Penyelenggara / Creator', $content);
    }

    public function test_administrator_can_reset_report_config_via_api(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $agenda = Agenda::first();
        $this->assertNotNull($agenda);

        $agenda->update(['report_config' => ['document_title' => 'Custom Title Testing']]);
        $this->assertNotNull($agenda->fresh()->report_config);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/reports/agendas/{$agenda->id}/config/reset");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Konfigurasi dokumen laporan berhasil direset ke standar sistem.');

        $this->assertNull($agenda->fresh()->report_config);
    }
}

