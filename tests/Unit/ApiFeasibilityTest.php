<?php

namespace Tests\Unit;

use App\Http\Requests\Agenda\StoreAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaRequest;
use App\Http\Requests\Agenda\UpdateMinutesRequest;
use App\Http\Requests\Agenda\UpdateSuratEdaranRequest;
use App\Models\Agenda;
use App\Models\User;
use App\Policies\AgendaPolicy;
use PHPUnit\Framework\TestCase;

class ApiFeasibilityTest extends TestCase
{
    private AgendaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AgendaPolicy();
    }

    public function test_agenda_model_supports_digital_circular_attributes(): void
    {
        $agenda = new Agenda([
            'id' => 99,
            'judul_rapat' => 'Rapat Koordinasi Evaluasi Anggaran',
            'surat_edaran_type' => 'digital',
            'surat_edaran_path' => 'surat_edaran/surat_digital_99.pdf',
            'status' => 'scheduled',
            'target_type' => 'all_units',
        ]);

        $this->assertSame('digital', $agenda->surat_edaran_type);
        $this->assertTrue($agenda->is_surat_edaran_digital);
        $this->assertIsArray($agenda->resolved_surat_edaran_config);
        $this->assertSame('undangan', $agenda->resolved_surat_edaran_config['template_type']);
    }

    public function test_store_and_update_agenda_requests_support_digital_type_and_selected_users(): void
    {
        $storeReq = new StoreAgendaRequest();
        $storeRules = $storeReq->rules();

        $this->assertArrayHasKey('surat_edaran_type', $storeRules);
        $this->assertArrayHasKey('target_type', $storeRules);
        $this->assertArrayHasKey('user_ids', $storeRules);
        $this->assertArrayHasKey('unit_ids', $storeRules);

        $updateReq = new UpdateAgendaRequest();
        $updateRules = $updateReq->rules();

        $this->assertArrayHasKey('surat_edaran_type', $updateRules);
        $this->assertArrayHasKey('target_type', $updateRules);
        $this->assertArrayHasKey('user_ids', $updateRules);
    }

    public function test_update_minutes_request_validates_existing_captions_for_mobile_api(): void
    {
        $request = new UpdateMinutesRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('existing_captions', $rules);
        $this->assertArrayHasKey('existing_captions.*', $rules);
        $this->assertSame(['nullable', 'array'], $rules['existing_captions']);
        $this->assertSame(['nullable', 'string', 'max:255'], $rules['existing_captions.*']);
    }

    public function test_update_digital_surat_edaran_request_rules_and_authorization(): void
    {
        $request = new UpdateSuratEdaranRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('template_type', $rules);
        $this->assertArrayHasKey('nomor_surat', $rules);
        $this->assertArrayHasKey('sifat', $rules);
        $this->assertArrayHasKey('perihal', $rules);
        $this->assertArrayHasKey('signer_role', $rules);
        $this->assertArrayHasKey('signer_name', $rules);

        $admin = new User();
        $admin->id = 1;
        $admin->role = 'administrator';

        $agenda = new Agenda(['id' => 10, 'created_by' => 1]);

        $this->assertTrue($this->policy->manageCircular($admin, $agenda));

        $otherStaff = new User();
        $otherStaff->id = 99;
        $otherStaff->role = 'staff';
        $otherStaff->unit_id = 5;

        $this->assertFalse($this->policy->manageCircular($otherStaff, $agenda));
    }
}
