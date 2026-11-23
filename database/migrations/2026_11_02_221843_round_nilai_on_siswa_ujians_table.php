<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('siswa_ujians')->update([
            'nilai' => DB::raw('ROUND(nilai)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nilai asli yang sudah dibulatkan tidak dapat dikembalikan.
    }
};
