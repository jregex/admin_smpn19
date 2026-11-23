<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('siswa_ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');

            $table->dateTime('waktu_mulai_kerja')->nullable();
            $table->dateTime('waktu_selesai_kerja')->nullable();
            $table->decimal('nilai', 5, 2)->default(0.00);
            $table->enum('status', ['belum_mulai', 'mengerjakan', 'selesai'])->default('belum_mulai');
            $table->timestamps();

            $table->unique(['ujian_id', 'siswa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa_ujians');
    }
};
