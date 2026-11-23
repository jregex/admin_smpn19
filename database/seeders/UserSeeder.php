<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Sekolah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::where('role', 'superadmin')->first();
        $roleAdmin = Role::where('role', 'admin_sekolah')->first();
        $roleGuru = Role::where('role', 'guru')->first();
        $smp = Sekolah::first();
        $users = [
            [
                'name' => 'Regex root',
                'role_id' => $role->id,
                'sekolah_id' => null,
                'email' => 'regex@admin.com',
                'password' => bcrypt('admin@123'),
                'is_active' => 1,
            ],
            [
                'name' => 'OPS19',
                'role_id' => $roleAdmin->id,
                'sekolah_id' => $smp->id,
                'email' => 'ops@smpn19.sch.id',
                'password' => bcrypt('admin@123'),
                'is_active' => 1,
            ],
        ];
        DB::table('users')->insert($users);
    }
}
