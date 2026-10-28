<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgendaFactoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_agenda_factory_creates_valid_agenda_with_real_users(): void
    {
        $agenda = Agenda::factory()->create();

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'judul_rapat' => $agenda->judul_rapat,
        ]);

        $this->assertNotNull($agenda->slug);
        $this->assertNotNull($agenda->created_by);
        $this->assertNotNull($agenda->pimpinan_id);
        $this->assertNotNull($agenda->notulis_id);
        $this->assertIsArray($agenda->report_config);
        $this->assertArrayHasKey('signer1_name', $agenda->report_config);
        $this->assertArrayHasKey('signer2_name', $agenda->report_config);
    }

    public function test_agenda_factory_lifecycle_states(): void
    {
        $scheduled = Agenda::factory()->scheduled()->create();
        $this->assertEquals('scheduled', $scheduled->status);
        $this->assertTrue($scheduled->waktu_mulai->isFuture());

        $ongoing = Agenda::factory()->ongoing()->create();
        $this->assertEquals('ongoing', $ongoing->status);
        $this->assertTrue($ongoing->waktu_mulai->isPast());

        $completed = Agenda::factory()->completed()->withNotulen()->create();
        $this->assertEquals('completed', $completed->status);
        $this->assertTrue($completed->waktu_mulai->isPast());
        $this->assertNotNull($completed->notulensi);
        $this->assertNotNull($completed->kesimpulan);
        $this->assertStringContainsString('Pembukaan dan Arahan Pimpinan', $completed->notulensi);

        $draft = Agenda::factory()->draft()->create();
        $this->assertEquals('draft', $draft->status);

        $cancelled = Agenda::factory()->cancelled()->create();
        $this->assertEquals('cancelled', $cancelled->status);
    }

    public function test_agenda_factory_format_states(): void
    {
        $offline = Agenda::factory()->offline('Aula Utama Lantai 3')->create();
        $this->assertEquals('offline', $offline->tipe_rapat);
        $this->assertStringContainsString('Aula Utama', $offline->lokasi_ruang);
        $this->assertNull($offline->link_meeting);

        $online = Agenda::factory()->online()->create();
        $this->assertEquals('online', $online->tipe_rapat);
        $this->assertNull($online->lokasi_ruang);
        $this->assertNotNull($online->link_meeting);

        $hybrid = Agenda::factory()->hybrid('Ruang Sidang Utama')->create();
        $this->assertEquals('hybrid', $hybrid->tipe_rapat);
        $this->assertNotNull($hybrid->lokasi_ruang);
        $this->assertNotNull($hybrid->link_meeting);
    }

    public function test_agenda_factory_for_units_attaches_pivot_units(): void
    {
        $unit = Unit::firstOrFail();

        $agenda = Agenda::factory()->forUnit($unit)->create();
        $this->assertFalse($agenda->is_all_units);
        $this->assertTrue($agenda->units()->where('units.id', $unit->id)->exists());

        $multiUnits = Unit::take(2)->get();
        $agendaMulti = Agenda::factory()->forUnits($multiUnits->all())->create();
        $this->assertFalse($agendaMulti->is_all_units);
        $this->assertCount(2, $agendaMulti->units);
    }

    public function test_agenda_factory_with_attendances_and_documentation(): void
    {
        $agenda = Agenda::factory()
            ->completed()
            ->withAttendances(5)
            ->withDocumentation(2)
            ->create();

        $this->assertCount(5, $agenda->attendances);
        $this->assertCount(2, $agenda->documentations);

        $attendance = $agenda->attendances->first();
        $this->assertNotNull($attendance->user_id);
        $this->assertNotNull($attendance->selfie_path);
        $this->assertNotNull($attendance->signature_path);
        $this->assertNotNull($attendance->signed_at);
        $this->assertStringStartsWith('10.10.', $attendance->ip_address);
    }

    public function test_attendance_factory_creates_valid_record(): void
    {
        $attendance = Attendance::factory()->create();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'agenda_id' => $attendance->agenda_id,
            'user_id' => $attendance->user_id,
        ]);
    }

    public function test_agenda_generate_artisan_command_executes_successfully(): void
    {
        $this->artisan('agenda:generate', [
            'count' => 3,
            '--status' => 'completed',
            '--attendees' => '2',
        ])
            ->expectsOutputToContain('Memulai Pembuatan 3 Agenda Rapat Kedinasan SIPERAPAT')
            ->expectsOutputToContain('Sukses: Berhasil membuat 3 data agenda rapat kedinasan.')
            ->assertExitCode(0);

        $this->assertTrue(Agenda::where('status', 'completed')->count() >= 3);
    }

    public function test_agenda_generate_command_validates_input(): void
    {
        $this->artisan('agenda:generate', [
            'count' => 0,
        ])
            ->expectsOutputToContain('Jumlah agenda harus berupa angka positif minimal 1.')
            ->assertExitCode(1);

        $this->artisan('agenda:generate', [
            'count' => 2,
            '--status' => 'invalid_status',
        ])
            ->expectsOutputToContain('Status tidak valid.')
            ->assertExitCode(1);

        $this->artisan('agenda:generate', [
            'count' => 2,
            '--type' => 'invalid_type',
        ])
            ->expectsOutputToContain('Tipe rapat tidak valid.')
            ->assertExitCode(1);
    }
}
