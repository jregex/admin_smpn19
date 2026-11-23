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
        Schema::create('siswa_jawaban_ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
            $table->foreignId('ujian_id')->constrained('ujians')->onDelete('cascade');
            $table->foreignId('soal_id')->constrained('soals')->onDelete('cascade');
            // Menyimpan huruf pilihan yang dipilih siswa (misal: 'A'), bernilai null jika tidak dijawab
            $table->string('jawaban_siswa', 2)->nullable();
            // 1 = Benar, 0 = Salah. Mempermudah kueri SUM() atau COUNT() saat kalkulasi nilai akhir
            $table->boolean('is_benar')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa_jawaban_ujians');
    }
};
