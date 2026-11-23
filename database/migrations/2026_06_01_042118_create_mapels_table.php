<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->onDelete('cascade');
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusans')->onDelete('set null');
            $table->string('kode_mapel', 20);
            $table->string('nama_mapel', 100);
            $table->enum('kelompok', ['Nasional', 'Kewilayahan', 'Kejuruan', 'Umum'])->default('Umum');
            $table->timestamps();

            $table->unique(['sekolah_id', 'kode_mapel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapels');
    }
};
