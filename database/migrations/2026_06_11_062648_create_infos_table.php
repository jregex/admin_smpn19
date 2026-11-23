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
        Schema::create('infos', function (Blueprint $table) {
            $table->id();
            // Relasi Multi-Tenant
            $table->foreignId('sekolah_id')->constrained('sekolahs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pembuat info

            // Konten
            $table->string('judul');
            $table->text('isi');
            $table->string('gambar')->nullable();
            $table->enum('target_audiens', ['semua', 'guru', 'siswa'])->default('semua');

            $table->boolean('is_pin')->default(false); // Untuk menyematkan info penting di atas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infos');
    }
};
