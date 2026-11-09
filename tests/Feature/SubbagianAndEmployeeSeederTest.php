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
            $this->assertTrue((bool) $unit->is_active, "Unit [{$kode}] harus berstatus aktif.");
        }
    }

    /**
     * Pastikan Sdr. Apandi terdaftar sebagai Administrator Utama dengan NIP, nama, email, dan unit_id null (lintas unit).
     */
    public function test_apandi_is_seeded_as_administrator_without_unit_constraint(): void
    {
        $admin = User::where('nip', '198804022019031016')->first();

        $this->assertNotNull($admin, 'Akun Apandi dengan NIP 198804022019031016 harus terdaftar.');
        $this->assertEquals('Apandi, ST', $admin->name);
        $this->assertEquals('apandi', $admin->username);
        $this->assertEquals('apandi@lldiktiwilayahx.kemdiktisaintek.go.id', $admin->email);
        $this->assertEquals(UserRole::Administrator, $admin->role);
        $this->assertNull($admin->unit_id, 'Administrator utama tidak boleh terikat unit_id tertentu (lintas unit).');
        $this->assertTrue((bool) $admin->is_active);
    }

    /**
     * Pastikan Ibu Afdalisma terdaftar sebagai Staff pada unit Bagian Tata Usaha dan BMN (BAG-TU).
     */
    public function test_afdalisma_is_seeded_as_staff_in_bag_tu(): void
    {
        $staff = User::where('nip', '197012051992032002')->first();

        $this->assertNotNull($staff, 'Akun Afdalisma dengan NIP 197012051992032002 harus terdaftar.');
        $this->assertEquals('Afdalisma, SH, M.Pd', $staff->name);
        $this->assertEquals('afdalisma', $staff->username);
        $this->assertEquals('afdalisma@lldiktiwilayahx.kemdiktisaintek.go.id', $staff->email);
        $this->assertEquals(UserRole::Staff, $staff->role);
        $this->assertEquals('BAG-TU', $staff->unit?->kode_unit, 'Ibu Afdalisma harus terikat ke unit BAG-TU.');
        $this->assertTrue((bool) $staff->is_active);
    }

    /**
     * Pastikan seluruh 68 pegawai lainnya terdaftar sebagai Staff aktif dan terikat pada unit masing-masing.
     */
    public function test_all_68_other_employees_are_seeded_as_staff_and_assigned_to_units(): void
    {
        $staffCount = User::where('role', UserRole::Staff->value)->where('is_active', true)->count();
        $this->assertEquals(68, $staffCount, 'Jumlah staff aktif harus tepat 68 orang.');

        $staffWithoutUnit = User::where('role', UserRole::Staff->value)->where('is_active', true)->whereNull('unit_id')->count();
        $this->assertEquals(0, $staffWithoutUnit, 'Seluruh staff aktif wajib terikat ke unit subbagian masing-masing.');

        $invalidEmails = User::where('is_active', true)->where('email', 'not like', '%@lldiktiwilayahx.kemdiktisaintek.go.id')->count();
        $this->assertEquals(0, $invalidEmails, 'Seluruh email pegawai harus menggunakan domain @lldiktiwilayahx.kemdiktisaintek.go.id.');

        $invalidNips = User::where('is_active', true)->whereRaw('LENGTH(nip) != 18')->count();
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
     * Validasi keakuratan 100% data terhadap berkas CSV resmi (69 Pegawai).
     */
    public function test_employee_data_accuracy_against_official_csv(): void
    {
        $csvPath = '/home/daffiq/.gemini/antigravity/brain/07e4da5a-5c0b-4560-8279-412c06b3e783/scratch/data_pegawai_terbaru_69.csv';
        $this->assertFileExists($csvPath);

        $handle = fopen($csvPath, 'r');
        fgetcsv($handle); // skip header

        $checkedCount = 0;
        while (($data = fgetcsv($handle)) !== false) {
            if (empty($data[1])) {
                continue;
            }

            $nip = trim($data[1]);
            $expectedName = trim($data[2]);
            $expectedUsername = trim($data[3]);
            $expectedEmail = trim($data[4]);
            $expectedRole = trim($data[5]);
            $expectedUnitCode = trim($data[6]);

            $user = User::where('nip', $nip)->first();
            $this->assertNotNull($user, "Pegawai dengan NIP {$nip} ({$expectedName}) harus ditemukan di database.");
            $this->assertEquals($expectedName, $user->name, "Nama pegawai [{$user->name}] harus cocok dengan [{$expectedName}].");
            $this->assertEquals($expectedUsername, $user->username, "Username pegawai [{$user->username}] harus cocok dengan [{$expectedUsername}].");
            $this->assertEquals($expectedEmail, $user->email, "Email pegawai [{$user->email}] harus cocok dengan [{$expectedEmail}].");
            $this->assertEquals($expectedRole, $user->role->value, "Role pegawai [{$user->role->value}] harus cocok dengan [{$expectedRole}].");

            if ($expectedUnitCode !== '') {
                $this->assertEquals($expectedUnitCode, $user->unit?->kode_unit, "Pegawai {$user->name} harus terhubung ke unit {$expectedUnitCode}.");
            } else {
                $this->assertNull($user->unit_id, "Pegawai {$user->name} tanpa unit_code harus memiliki unit_id null.");
            }

            $checkedCount++;
        }
        fclose($handle);

        $this->assertEquals(69, $checkedCount, 'Harus memvalidasi tepat 69 pegawai resmi.');
    }
}
