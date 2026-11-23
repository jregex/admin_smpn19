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
        Schema::create('pilihan_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soals')->onDelete('cascade');
            $table->string('opsi', 2); // Menyimpan karakter 'A', 'B', 'C', 'D', atau 'E'
            $table->text('teks_pilihan');
            $table->boolean('is_kunci')->default(false); // Penanda biner pelengkap
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilihan_jawabans');
    }
};
