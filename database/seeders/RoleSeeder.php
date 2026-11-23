<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role' => 'superadmin',
            ],
            [
                'role' => 'admin_sekolah',
            ],
            [
                'role' => 'guru',
            ],
            [
                'role' => 'siswa',
            ],
        ];
        DB::table('roles')->insert($roles);
    }
}
