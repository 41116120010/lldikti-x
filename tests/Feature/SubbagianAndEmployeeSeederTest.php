<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class SubbagianAndEmployeeSeederTest extends TestCase
{
    /**
     * Pastikan seluruh 11 Subbagian resmi instansi terdaftar dengan kode unit yang benar dan aktif.
     */
    public function test_all_11_subbagian_are_seeded_with_correct_codes_and_active_status(): void
    {
        $expectedCodes = [
            'BAG-AKM' => 'Bagian Akademik dan Kemahasiswaan',
            'BAG-SDPT' => 'Bagian Sumber Daya Perguruan Tinggi',
            'POKJA-PTK' => 'Pendidik dan Tenaga Kependidikan',
            'POKJA-KLB' => 'Kelembagaan',
            'POKJA-AKM' => 'Akademik',
            'SUBBAG-HKTL' => 'Hukum, Kepegawaian dan Tata Laksana',
            'SUBBAG-KMH' => 'Kemahasiswaan',
            'BAG-TU' => 'Tata Usaha dan Barang Milik Negara',
            'SUBBAG-SIKS' => 'Sistem Informasi dan Kerja Sama',
            'SUBBAG-PP' => 'Perencanaan dan Penganggaran',
            'SUBBAG-SARPRAS' => 'Sarana dan Prasarana',
        ];

        foreach ($expectedCodes as $kode => $nama) {
            $unit = Unit::where('kode_unit', $kode)->first();
            $this->assertNotNull($unit, "Unit dengan kode [{$kode}] harus terdaftar di database.");
            $this->assertEquals($nama, $unit->nama_unit);
            $this->assertTrue((bool)$unit->is_active, "Unit [{$kode}] harus berstatus aktif.");
        }
    }

    /**
     * Pastikan Ibu Afdalisma terdaftar sebagai Administrator dengan NIP, gelar, email, dan unit_id null (lintas unit).
     */
    public function test_afdalisma_is_seeded_as_administrator_without_unit_constraint(): void
    {
        $admin = User::where('nip', '197012051992032002')->first();

        $this->assertNotNull($admin, 'Akun Afdalisma dengan NIP 197012051992032002 harus terdaftar.');
        $this->assertEquals('Afdalisma, SH, M.Pd', $admin->name);
        $this->assertEquals('afdalisma', $admin->username);
        $this->assertEquals('afdalisma@lldiktiwilayahx.kemdiktisaintek.go.id', $admin->email);
        $this->assertEquals(UserRole::Administrator, $admin->role);
        $this->assertNull($admin->unit_id, 'Administrator utama tidak boleh terikat unit_id tertentu (lintas unit).');
        $this->assertTrue((bool)$admin->is_active);
    }

    /**
     * Pastikan seluruh 78 pegawai lainnya terdaftar sebagai Staff dan terikat pada unit masing-masing.
     */
    public function test_all_78_other_employees_are_seeded_as_staff_and_assigned_to_units(): void
    {
        $staffCount = User::where('role', UserRole::Staff->value)->count();
        $this->assertEquals(78, $staffCount, 'Jumlah staff harus tepat 78 orang.');

        $staffWithoutUnit = User::where('role', UserRole::Staff->value)->whereNull('unit_id')->count();
        $this->assertEquals(0, $staffWithoutUnit, 'Seluruh staff wajib terikat ke unit subbagian masing-masing.');

        $invalidEmails = User::where('email', 'not like', '%@lldiktiwilayahx.kemdiktisaintek.go.id')->count();
        $this->assertEquals(0, $invalidEmails, 'Seluruh email pegawai harus menggunakan domain @lldiktiwilayahx.kemdiktisaintek.go.id.');

        $invalidNips = User::whereRaw('LENGTH(nip) != 18')->count();
        $this->assertEquals(0, $invalidNips, 'Seluruh NIP pegawai wajib berupa 18 digit.');
    }

    /**
     * Pastikan autentikasi multi-identifier (NIP 18-digit & Username) berfungsi sempurna dengan password default.
     */
    public function test_login_multi_identifier_works_for_administrator_and_staff(): void
    {
        // 1. Login Administrator via NIP
        $admin = User::where('role', UserRole::Administrator->value)->firstOrFail();
        $resAdminNip = $this->post('/login', [
            'login' => $admin->nip,
            'password' => 'Password123!',
        ]);
        $resAdminNip->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout');
        $this->assertGuest();

        // 2. Login Administrator via Username
        $resAdminUser = $this->post('/login', [
            'login' => $admin->username,
            'password' => 'Password123!',
        ]);
        $resAdminUser->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout');
        $this->assertGuest();

        // 3. Login Staff via NIP
        $staff = User::where('role', UserRole::Staff->value)->firstOrFail();
        $resStaffNip = $this->post('/login', [
            'login' => $staff->nip,
            'password' => 'Password123!',
        ]);
        $resStaffNip->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($staff);

        $this->post('/logout');
        $this->assertGuest();

        // 4. Login Staff via Username
        $resStaffUser = $this->post('/login', [
            'login' => $staff->username,
            'password' => 'Password123!',
        ]);
        $resStaffUser->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($staff);
    }

    /**
     * Validasi keakuratan 100% data terhadap berkas CSV resmi.
     */
    public function test_employee_data_accuracy_against_official_csv(): void
    {
        $csvPath = '/home/daffiq/.gemini/antigravity/brain/07e4da5a-5c0b-4560-8279-412c06b3e783/scratch/data_karyawan_baru.csv';
        $this->assertFileExists($csvPath);

        $handle = fopen($csvPath, 'r');
        fgetcsv($handle); // skip header

        $subbagianMap = [
            'KEPALA BAGIAN AKADEMIK DAN KEMAHASISWAAN' => 'BAG-AKM',
            'KEPALA BAGIAN SUMBER DAYA PERGURUAN TINGGI' => 'BAG-SDPT',
            'PENDIDIK dan TENAGA KEPENDIDIKAN' => 'POKJA-PTK',
            'KELEMBAGAAN' => 'POKJA-KLB',
            'AKADEMIK' => 'POKJA-AKM',
            'HUKUM, KEPEGAWAIAN dan TATA LAKSANA' => 'SUBBAG-HKTL',
            'KEMAHASISWAAN' => 'SUBBAG-KMH',
            'TATA USAHA dan BARANG MILIK NEGARA' => 'BAG-TU',
            'SISTEM INFORMASI dan KERJA SAMA' => 'SUBBAG-SIKS',
            'PERENCANAAN DAN PENGANGGARAN' => 'SUBBAG-PP',
            'SARANA dan PRASARANA' => 'SUBBAG-SARPRAS',
        ];

        $checkedCount = 0;
        while (($data = fgetcsv($handle)) !== false) {
            if (empty($data[1])) {
                continue;
            }

            $nip = trim($data[1]);
            $rawNama = trim($data[2]);
            $rawGelar = isset($data[3]) ? trim($data[3]) : '';
            $cleanGelar = rtrim(trim($rawGelar), " ,\t\n\r\0\x0B");
            $rawSubbag = isset($data[7]) ? trim($data[7]) : '';

            $user = User::where('nip', $nip)->first();
            $this->assertNotNull($user, "Pegawai dengan NIP {$nip} ({$rawNama}) harus ditemukan di database.");

            // Periksa Gelar jika ada
            if ($cleanGelar !== '') {
                $this->assertStringContainsString($cleanGelar, $user->name, "Nama [{$user->name}] harus memuat gelar [{$cleanGelar}].");
            }

            // Periksa Asosiasi Subbagian
            if ($rawSubbag !== '') {
                $expectedUnitCode = $subbagianMap[$rawSubbag] ?? null;
                $this->assertNotNull($expectedUnitCode);
                $this->assertEquals($expectedUnitCode, $user->unit?->kode_unit, "Pegawai {$user->name} harus terhubung ke unit {$expectedUnitCode}.");
            }

            $checkedCount++;
        }
        fclose($handle);

        $this->assertEquals(79, $checkedCount, 'Harus memvalidasi tepat 79 pegawai.');
    }
}
