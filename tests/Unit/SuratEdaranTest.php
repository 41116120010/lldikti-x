<?php

namespace Tests\Unit;

use App\Http\Requests\Agenda\UpdateSuratEdaranRequest;
use App\Models\Agenda;
use App\Models\User;
use App\Policies\AgendaPolicy;
use App\Support\Html;
use PHPUnit\Framework\TestCase;

class SuratEdaranTest extends TestCase
{
    private AgendaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AgendaPolicy();
    }

    public function test_is_surat_edaran_digital_attribute(): void
    {
        $digitalAgenda = new Agenda(['surat_edaran_type' => 'digital']);
        $this->assertTrue($digitalAgenda->is_surat_edaran_digital);

        $uploadAgenda = new Agenda(['surat_edaran_type' => 'upload']);
        $this->assertFalse($uploadAgenda->is_surat_edaran_digital);

        $nullAgenda = new Agenda();
        $this->assertFalse($nullAgenda->is_surat_edaran_digital);
    }

    public function test_default_surat_edaran_config_structure(): void
    {
        $agenda = new Agenda([
            'judul_rapat' => 'Rapat Koordinasi Mutu Pendidikan Tinggi',
            'lokasi_ruang' => 'Ruang Sidang Utama',
            'link_meeting' => 'https://meet.google.com/lldikti-x',
        ]);

        $defaultConfig = $agenda->getDefaultSuratEdaranConfig();

        $this->assertIsArray($defaultConfig);
        $this->assertArrayHasKey('template_type', $defaultConfig);
        $this->assertSame('undangan', $defaultConfig['template_type']);
        $this->assertArrayHasKey('nomor_surat', $defaultConfig);
        $this->assertArrayHasKey('sifat', $defaultConfig);
        $this->assertArrayHasKey('lampiran', $defaultConfig);
        $this->assertArrayHasKey('perihal', $defaultConfig);
        $this->assertArrayHasKey('tujuan_surat', $defaultConfig);
        $this->assertArrayHasKey('signer_role', $defaultConfig);
        $this->assertArrayHasKey('signer_name', $defaultConfig);
        $this->assertArrayHasKey('signer_nip', $defaultConfig);
        $this->assertArrayHasKey('tembusan', $defaultConfig);
    }

    public function test_resolved_surat_edaran_config_merges_custom_values(): void
    {
        $agenda = new Agenda([
            'judul_rapat' => 'Rapat Khusus Pimpinan',
            'surat_edaran_config' => [
                'template_type' => 'edaran',
                'nomor_surat' => '123/LL10/KP/2026',
                'sifat' => 'Sangat Rahasia',
                'signer_name' => 'Ibu Kepala Lembaga',
                'signer_nip' => '197001011995032001',
            ],
        ]);

        $resolved = $agenda->resolved_surat_edaran_config;

        $this->assertSame('edaran', $resolved['template_type']);
        $this->assertSame('123/LL10/KP/2026', $resolved['nomor_surat']);
        $this->assertSame('Sangat Rahasia', $resolved['sifat']);
        $this->assertSame('Ibu Kepala Lembaga', $resolved['signer_name']);
        $this->assertSame('197001011995032001', $resolved['signer_nip']);
        // Verify default keys that were not overwritten are still present
        $this->assertArrayHasKey('instansi_induk', $resolved);
        $this->assertArrayHasKey('instansi_pelaksana', $resolved);
        $this->assertArrayHasKey('tempat_surat', $resolved);
    }

    public function test_update_surat_edaran_request_rules(): void
    {
        $request = new UpdateSuratEdaranRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('template_type', $rules);
        $this->assertArrayHasKey('nomor_surat', $rules);
        $this->assertArrayHasKey('sifat', $rules);
        $this->assertArrayHasKey('lampiran', $rules);
        $this->assertArrayHasKey('perihal', $rules);
        $this->assertArrayHasKey('tujuan_surat', $rules);
        $this->assertArrayHasKey('surat_edaran_content', $rules);
        $this->assertArrayHasKey('signer_role', $rules);
        $this->assertArrayHasKey('signer_name', $rules);
        $this->assertArrayHasKey('signer_nip', $rules);
        $this->assertArrayHasKey('custom_logo', $rules);
    }

    public function test_html_sanitize_strips_unsafe_tags(): void
    {
        $dirty = '<p>Halo Rekan ASN <script>alert("xss")</script><a href="javascript:steal()">Link</a></p>';
        $clean = Html::sanitize($dirty);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('Halo Rekan ASN', $clean);
    }

    public function test_agenda_policy_manage_circular_for_administrator(): void
    {
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'administrator';

        $agenda = new Agenda();

        $this->assertTrue($this->policy->manageCircular($admin, $agenda));
    }

    public function test_agenda_policy_manage_circular_for_creator(): void
    {
        $creator = new User();
        $creator->id = 5;
        $creator->role = 'admin';

        $agenda = new Agenda(['created_by' => 5]);

        $this->assertTrue($this->policy->manageCircular($creator, $agenda));
    }

    public function test_agenda_policy_manage_circular_for_organizing_unit(): void
    {
        $unitAdmin = new User();
        $unitAdmin->id = 8;
        $unitAdmin->role = 'admin';
        $unitAdmin->unit_id = 2;

        $creatorUser = new User();
        $creatorUser->id = 9;
        $creatorUser->unit_id = 2;

        $agenda = new Agenda(['created_by' => 9]);
        $agenda->setRelation('creator', $creatorUser);

        $this->assertTrue($this->policy->manageCircular($unitAdmin, $agenda));
    }

    public function test_agenda_policy_manage_circular_denied_for_unrelated_staff(): void
    {
        $staff = new User();
        $staff->id = 15;
        $staff->role = 'staff';
        $staff->unit_id = 3;

        $creatorUser = new User();
        $creatorUser->id = 9;
        $creatorUser->unit_id = 2;

        $agenda = new Agenda(['created_by' => 9]);
        $agenda->setRelation('creator', $creatorUser);

        $this->assertFalse($this->policy->manageCircular($staff, $agenda));
    }
}
