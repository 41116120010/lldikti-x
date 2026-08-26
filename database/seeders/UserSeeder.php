<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitAkm = Unit::where('kode_unit', 'POKJA-AKM')->first();
        $unitKlb = Unit::where('kode_unit', 'POKJA-KLB')->first();
        $unitTu  = Unit::where('kode_unit', 'BAG-TU')->first();

        $defaultPassword = Hash::make('Password123!');

        $users = [
            [
                'unit_id' => null,
                'name' => 'Dr. Ir. Hendra Prasetyo, M.T.',
                'nip' => '197501152000031001',
                'username' => 'superadmin',
                'email' => 'admin.siperapat@lldikti.kemdikbud.go.id',
                'password' => $defaultPassword,
                'role' => 'administrator',
                'phone' => '081234567890',
                'is_active' => true,
            ],
            [
                'unit_id' => $unitAkm?->id,
                'name' => 'Drs. Bambang Sudarmono, M.Si.',
                'nip' => '198005202005011002',
                'username' => 'admin_akademik',
                'email' => 'bambang.akademik@lldikti.kemdikbud.go.id',
                'password' => $defaultPassword,
                'role' => 'admin',
                'phone' => '081234567891',
                'is_active' => true,
            ],
            [
                'unit_id' => $unitKlb?->id,
                'name' => 'Dewi Lestari, S.Kom., M.Kom.',
                'nip' => '198508122008122003',
                'username' => 'admin_kelembagaan',
                'email' => 'dewi.kelembagaan@lldikti.kemdikbud.go.id',
                'password' => $defaultPassword,
                'role' => 'admin',
                'phone' => '081234567892',
                'is_active' => true,
            ],
            [
                'unit_id' => $unitAkm?->id,
                'name' => 'Rizky Fauzi, S.T.',
                'nip' => '199402142020121004',
                'username' => 'staff_rizky',
                'email' => 'rizky.fauzi@lldikti.kemdikbud.go.id',
                'password' => $defaultPassword,
                'role' => 'staff',
                'phone' => '081234567893',
                'is_active' => true,
            ],
            [
                'unit_id' => $unitKlb?->id,
                'name' => 'Nurul Hidayah, S.Sos.',
                'nip' => '199611252022032005',
                'username' => 'staff_nurul',
                'email' => 'nurul.hidayah@lldikti.kemdikbud.go.id',
                'password' => $defaultPassword,
                'role' => 'staff',
                'phone' => '081234567894',
                'is_active' => true,
            ],
            [
                'unit_id' => $unitTu?->id,
                'name' => 'Ahmad Syukron, A.Md.',
                'nip' => '199806102023051006',
                'username' => 'staff_ahmad',
                'email' => 'ahmad.syukron@lldikti.kemdikbud.go.id',
                'password' => $defaultPassword,
                'role' => 'staff',
                'phone' => '081234567895',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                $user
            );
        }
    }
}
