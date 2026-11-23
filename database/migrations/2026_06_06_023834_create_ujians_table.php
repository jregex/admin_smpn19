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
        Schema::create('ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->onDelete('cascade');
            // KUNCI: Diarahkan ke tabel gurus
            $table->foreignId('guru_id')->constrained('gurus')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->foreignId('pengawas_id')->nullable()->constrained('gurus')->onDelete('set null');
            $table->foreignId('mapel_id')->nullable()->constrained('mapels')->onDelete('set null');
            $table->string('nama_ujian', 150);
            $table->text('deskripsi')->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->integer('durasi');
            $table->integer('kkpp')->default(75);
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['BELUM MULAI', 'BERLANGSUNG', 'DITUNDA', 'OFFLINE', 'SELESAI'])->default('BELUM MULAI');
            $table->timestamps();

            $table->index(['waktu_mulai', 'waktu_selesai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujians');
    }
};
