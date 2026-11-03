<?php

namespace Tests\Unit;

use App\Models\Agenda;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

class AgendaDashboardRelevanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('Asia/Jakarta');
    }

    public function test_scheduled_agenda_past_end_time_is_overdue(): void
    {
        $pastAgenda = new Agenda();
        $pastAgenda->setRawAttributes([
            'status' => 'scheduled',
            'waktu_mulai' => Carbon::now()->subHours(4)->toDateTimeString(),
            'waktu_selesai' => Carbon::now()->subHours(2)->toDateTimeString(),
        ]);

        $this->assertTrue($pastAgenda->is_overdue);
        $this->assertSame('Terlewat', $pastAgenda->status_meta_soft['label']);
        $this->assertStringContainsString('bg-amber-50', $pastAgenda->status_meta_soft['class']);
    }

    public function test_scheduled_agenda_future_end_time_is_not_overdue(): void
    {
        $futureAgenda = new Agenda([
            'status' => 'scheduled',
            'waktu_mulai' => Carbon::now()->addHour(),
            'waktu_selesai' => Carbon::now()->addHours(3),
        ]);

        $this->assertFalse($futureAgenda->is_overdue);
        $this->assertSame('Terjadwal', $futureAgenda->status_meta_soft['label']);
        $this->assertStringContainsString('bg-blue-50', $futureAgenda->status_meta_soft['class']);
    }

    public function test_ongoing_agenda_status_meta_soft(): void
    {
        $ongoingAgenda = new Agenda([
            'status' => 'ongoing',
            'waktu_mulai' => Carbon::now()->subMinutes(30),
            'waktu_selesai' => Carbon::now()->addHours(2),
        ]);

        $this->assertFalse($ongoingAgenda->is_overdue);
        $this->assertSame('Sedang Berlangsung', $ongoingAgenda->status_meta_soft['label']);
        $this->assertStringContainsString('bg-emerald-50', $ongoingAgenda->status_meta_soft['class']);
    }

    public function test_completed_agenda_status_meta_soft(): void
    {
        $completedAgenda = new Agenda([
            'status' => 'completed',
            'waktu_mulai' => Carbon::now()->subDays(1),
            'waktu_selesai' => Carbon::now()->subDays(1)->addHours(2),
        ]);

        $this->assertFalse($completedAgenda->is_overdue);
        $this->assertSame('Selesai', $completedAgenda->status_meta_soft['label']);
        $this->assertStringContainsString('bg-slate-100', $completedAgenda->status_meta_soft['class']);
    }

    public function test_cancelled_agenda_status_meta_soft(): void
    {
        $cancelledAgenda = new Agenda([
            'status' => 'cancelled',
            'waktu_mulai' => Carbon::now()->addDays(2),
            'waktu_selesai' => Carbon::now()->addDays(2)->addHours(2),
        ]);

        $this->assertFalse($cancelledAgenda->is_overdue);
        $this->assertSame('Dibatalkan', $cancelledAgenda->status_meta_soft['label']);
        $this->assertStringContainsString('bg-rose-50', $cancelledAgenda->status_meta_soft['class']);
    }

    public function test_can_start_now_within_window(): void
    {
        // Meeting scheduled 5 minutes from now (within 15 min start window)
        $agenda = new Agenda([
            'status' => 'scheduled',
            'waktu_mulai' => Carbon::now()->addMinutes(5),
            'waktu_selesai' => Carbon::now()->addHours(2),
        ]);

        $this->assertTrue($agenda->can_start_now);
    }

    public function test_can_start_now_outside_window(): void
    {
        // Meeting scheduled tomorrow (outside 15 min start window)
        $agendaTomorrow = new Agenda([
            'status' => 'scheduled',
            'waktu_mulai' => Carbon::now()->addDay(),
            'waktu_selesai' => Carbon::now()->addDay()->addHours(2),
        ]);

        $this->assertFalse($agendaTomorrow->can_start_now);

        // Meeting scheduled 3 hours ago with 1 hour duration (past effective end)
        $agendaPast = new Agenda([
            'status' => 'scheduled',
            'waktu_mulai' => Carbon::now()->subHours(3),
            'waktu_selesai' => Carbon::now()->subHours(2),
        ]);

        $this->assertFalse($agendaPast->can_start_now);
    }
}
