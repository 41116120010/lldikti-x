<?php

namespace Tests\Unit;

use App\Http\Requests\Agenda\UpdateSuratEdaranRequest;
use App\Models\Agenda;
use App\Models\User;
use App\Policies\AgendaPolicy;
use App\Services\DompdfRenderer;
use App\Services\SuratEdaranService;
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
        $this->assertSame('Kepala', $defaultConfig['signer_role']);
        $this->assertArrayHasKey('signer_name', $defaultConfig);
        $this->assertSame('Afdalisma', $defaultConfig['signer_name']);
        $this->assertArrayHasKey('signer_nip', $defaultConfig);
        $this->assertSame('197012051992032002', $defaultConfig['signer_nip']);
        $this->assertArrayHasKey('tembusan', $defaultConfig);
        $this->assertArrayHasKey('show_lampiran', $defaultConfig);
        $this->assertArrayHasKey('lampiran_content', $defaultConfig);
        $this->assertStringContainsString('lldikti10.kemdiktisaintek.go.id', $defaultConfig['alamat_kontak']);
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

    public function test_digital_surat_edaran_resolved_config_integrity(): void
    {
        $agenda = new Agenda([
            'id' => 42,
            'judul_rapat' => 'Rapat Penjaminan Mutu Perguruan Tinggi',
            'surat_edaran_type' => 'digital',
            'surat_edaran_config' => [
                'nomor_surat' => '123/LL10/TU/2026',
                'tempat_surat' => 'Padang',
                'tanggal_surat' => '04 November 2026',
                'tujuan_surat' => 'Yth. Pimpinan Perguruan Tinggi Swasta',
                'template_type' => 'undangan',
            ],
        ]);

        $this->assertTrue($agenda->is_surat_edaran_digital);
        $config = $agenda->resolved_surat_edaran_config;

        $this->assertSame('123/LL10/TU/2026', $config['nomor_surat']);
        $this->assertSame('Padang', $config['tempat_surat']);
        $this->assertSame('04 November 2026', $config['tanggal_surat']);
        $this->assertSame('undangan', $config['template_type']);
        $this->assertSame('Yth. Pimpinan Perguruan Tinggi Swasta', $config['tujuan_surat']);
        $this->assertSame('Kepala', $config['signer_role']);
        $this->assertSame('Afdalisma', $config['signer_name']);
        $this->assertSame('197012051992032002', $config['signer_nip']);
    }

    public function test_legacy_surat_edaran_config_normalizes_to_official_template_standard(): void
    {
        $agenda = new Agenda([
            'id' => 99,
            'judul_rapat' => 'Rapat Koordinasi Lama',
            'surat_edaran_type' => 'digital',
            'surat_edaran_config' => [
                'instansi_induk' => 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI',
                'instansi_pelaksana' => 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X',
                'alamat_kontak' => 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id',
                'signer_role' => 'Kepala Lembaga Layanan Pendidikan Tinggi Wilayah X',
                'signer_name' => 'Afdalisma, S.H., M.Pd.',
                'signer_nip' => '196909281994032001',
            ],
        ]);

        $resolved = $agenda->resolved_surat_edaran_config;

        $this->assertSame("KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI", $resolved['instansi_induk']);
        $this->assertSame('LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X', $resolved['instansi_pelaksana']);
        $this->assertStringContainsString('lldikti10.kemdiktisaintek.go.id', $resolved['alamat_kontak']);
        $this->assertStringContainsString('info.lldikti10@kemdiktisaintek.go.id', $resolved['alamat_kontak']);
        $this->assertSame('Kepala', $resolved['signer_role']);
        $this->assertSame('Afdalisma', $resolved['signer_name']);
        $this->assertSame('197012051992032002', $resolved['signer_nip']);
    }

    public function test_html_sanitize_preserves_editor_table_and_font_attributes(): void
    {
        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; margin:10px 0;"><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px 10px;"><font face="Times New Roman" size="4" color="#1e3a8a">Agenda Pembahasan</font></td></tr></tbody></table>';
        $sanitized = Html::sanitize($html);

        $this->assertStringContainsString('<table border="1" cellpadding="6" cellspacing="0"', $sanitized);
        $this->assertStringContainsString('border-collapse: collapse', $sanitized);
        $this->assertStringContainsString('border: 1px solid #cbd5e1', $sanitized);
        $this->assertStringContainsString('<font face="Times New Roman" size="4" color="#1e3a8a">Agenda Pembahasan</font>', $sanitized);
    }

    public function test_update_surat_edaran_request_messages_and_defaults(): void
    {
        $request = new UpdateSuratEdaranRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('custom_logo.image', $messages);
        $this->assertArrayHasKey('custom_logo.mimes', $messages);
        $this->assertArrayHasKey('custom_logo.max', $messages);
        $this->assertArrayHasKey('reset_custom_logo', $request->rules());
    }

    public function test_surat_edaran_service_has_generate_pdf_alias(): void
    {
        $this->assertTrue(method_exists(SuratEdaranService::class, 'generatePdf'));
        $this->assertTrue(method_exists(SuratEdaranService::class, 'renderPdf'));
        $this->assertTrue(method_exists(SuratEdaranService::class, 'downloadPdfResponse'));
        $this->assertTrue(method_exists(DompdfRenderer::class, 'render'));
    }

    public function test_surat_edaran_workstation_view_and_js_parity_with_notulen(): void
    {
        $bladePath = dirname(__DIR__, 2) . '/resources/views/agendas/surat_edaran.blade.php';
        $jsPath = dirname(__DIR__, 2) . '/resources/js/app.js';
        $cssPath = dirname(__DIR__, 2) . '/resources/css/app.css';

        $blade = (string) file_get_contents($bladePath);
        $js = (string) file_get_contents($jsPath);
        $css = (string) file_get_contents($cssPath);

        // Verify ribbon commands parity with notulen editor
        $this->assertStringContainsString('data-command="superscript"', $blade);
        $this->assertStringContainsString('data-command="subscript"', $blade);
        $this->assertStringContainsString('data-command="blockquote"', $blade);
        $this->assertStringContainsString('data-action="clearAll"', $blade);
        $this->assertStringContainsString('<option value="3" selected>12pt (Standar)</option>', $blade);
        $this->assertStringNotContainsString('<option value="">12pt (Standar)</option>', $blade);

        // Verify status bar parity
        $this->assertStringContainsString('id="office-status-page"', $blade);
        $this->assertStringContainsString('id="office-status-size"', $blade);
        $this->assertStringContainsString('id="office-status-zoom"', $blade);

        // Verify JS selection isolation and keyboard shortcuts
        $this->assertStringContainsString('isNodeInsideEditableBox', $js);
        $this->assertStringContainsString('sel.collapseToEnd()', $js);
        $this->assertStringContainsString("l: 'justifyLeft'", $js);
        $this->assertStringContainsString("e: 'justifyCenter'", $js);
        $this->assertStringContainsString("r: 'justifyRight'", $js);
        $this->assertStringContainsString("j: 'justifyFull'", $js);

        // Verify CSS font-smoothing stabilization and canvas focus-ring protection
        $this->assertStringNotContainsString('.office-desk-canvas :focus-visible:not(.office-editable-box)', $css);
        $this->assertStringContainsString('-webkit-font-smoothing: antialiased;', $css);
    }
}
