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
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel ujians, otomatis hapus soal jika data ujian dihapus
            $table->foreignId('ujian_id')->constrained('ujians')->onDelete('cascade');
            $table->text('teks_soal');
            // Menyimpan string kunci jawaban langsung ('A', 'B', 'C', 'D', 'E')
            $table->string('kunci_jawaban', 2);
            $table->string('gambar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soals');
    }
};
