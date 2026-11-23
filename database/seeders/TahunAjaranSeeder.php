<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjarans = [
            [
                'sekolah_id' => 1,
                'tahun' => '2024/2025',
                'tanggal_mulai' => '2024-07-15',
                'tanggal_selesai' => '2025-06-30',
                'is_aktif' => false,
            ],
            [
                'sekolah_id' => 1,
                'tahun' => '2025/2026',
                'tanggal_mulai' => '2025-07-14',
                'tanggal_selesai' => '2026-06-30',
                'is_aktif' => true,
            ],
        ];

        DB::table('tahun_ajarans')->insert($tahunAjarans);
    }
}
