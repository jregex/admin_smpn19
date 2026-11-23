<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Sekolah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sekolah = Sekolah::where('nama_sekolah', 'SMPN 19 Tikep')->firstOrFail();
        $role = Role::where('role', 'siswa')->firstOrFail();
        $rombel = DB::table('rombels')->where('sekolah_id', $sekolah->id)->first();

        if (! $rombel) {
            $this->command?->error('Rombel belum tersedia. Jalankan seeder rombel terlebih dahulu.');

            return;
        }

        $siswas = [
            [
                'name' => 'Sriyanti',
                'email' => 'sriyanti@smpn19.sch.id',
                'nisn' => '0051234567',
                'agama' => 'Islam',
                'jenis_kelamin' => 'P',
            ],
            [
                'name' => 'Rahman',
                'email' => 'rahman@smpn19.sch.id',
                'nisn' => '0051234568',
                'agama' => 'Islam',
                'jenis_kelamin' => 'L',
            ],
        ];

        DB::transaction(function () use ($siswas, $sekolah, $role, $rombel): void {
            foreach ($siswas as $data) {
                DB::table('users')->updateOrInsert(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'role_id' => $role->id,
                        'sekolah_id' => $sekolah->id,
                        'password' => bcrypt('siswa@123'),
                        'is_active' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $userId = DB::table('users')->where('email', $data['email'])->value('id');

                DB::table('siswas')->updateOrInsert(
                    ['sekolah_id' => $sekolah->id, 'nisn' => $data['nisn']],
                    [
                        'rombel_id' => $rombel->id,
                        'user_id' => $userId,
                        'nama' => $data['name'],
                        'agama' => $data['agama'],
                        'jenis_kelamin' => $data['jenis_kelamin'],
                        'tempat_lahir' => '-',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });
    }
}
