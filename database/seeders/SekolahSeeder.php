<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SekolahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sekolahs = [
            [
                'nama_sekolah' => 'SMPN 19 Tikep',
                'jenjang' => 'SMP',
            ],
        ];
        DB::table('sekolahs')->insert($sekolahs);
    }
}
