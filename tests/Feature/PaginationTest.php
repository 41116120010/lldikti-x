<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    public function test_dashboard_renders_with_paginated_agendas(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('activeAgendas');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('activeAgendas'));
    }

    public function test_profile_activity_logs_renders_with_pagination(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->get('/profil/aktivitas');

        $response->assertStatus(200);
        $response->assertViewHas('logs');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('logs'));
    }

    public function test_reports_index_renders_with_paginated_agendas_and_unit_participation(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertViewHas('agendas');
        $response->assertViewHas('unitStats');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('agendas'));
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('unitStats'));
    }

    public function test_reports_index_for_admin_unit_renders_with_paginated_member_stats(): void
    {
        $adminUnit = User::where('role', 'admin')->first();

        $response = $this->actingAs($adminUnit)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertViewHas('agendas');
        $response->assertViewHas('memberStats');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('agendas'));
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('memberStats'));
    }

    public function test_report_detail_renders_with_paginated_attendances(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/reports/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertViewHas('attendances');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('attendances'));
    }

    public function test_agenda_detail_renders_with_paginated_attendances_and_documentations(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}");

        $response->assertStatus(200);
        $response->assertViewHas('attendances');
        $response->assertViewHas('documentations');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('attendances'));
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('documentations'));
    }

    public function test_agenda_notulen_renders_with_paginated_documentations(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $agenda = Agenda::first();

        $response = $this->actingAs($superadmin)->get("/admin/agendas/{$agenda->id}/notulen");

        $response->assertStatus(200);
        $response->assertViewHas('documentations');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('documentations'));
    }

    public function test_unit_edit_renders_with_paginated_unit_users(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $response = $this->actingAs($superadmin)->get("/admin/units/{$unit->id}/edit");

        $response->assertStatus(200);
        $response->assertViewHas('unitUsers');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('unitUsers'));
    }

    public function test_user_edit_renders_with_paginated_recent_attendances(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $user = User::first();

        $response = $this->actingAs($superadmin)->get("/admin/users/{$user->id}/edit");

        $response->assertStatus(200);
        $response->assertViewHas('recentAttendances');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('recentAttendances'));
    }

    public function test_attendance_portal_renders_with_paginated_lists(): void
    {
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($staff)->get('/presensi');

        $response->assertStatus(200);
        $response->assertViewHas('ongoingAgendas');
        $response->assertViewHas('scheduledAgendas');
        $response->assertViewHas('recentAttendances');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('ongoingAgendas'));
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('scheduledAgendas'));
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\Paginator::class, $response->viewData('recentAttendances'));
    }
}
