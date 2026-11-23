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
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nip', 22)->nullable(); // Di-nullable jika ada guru honorer tanpa NIP
            $table->string('nama', 150);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('no_telp', 15)->nullable();
            $table->timestamps();

            $table->unique(['sekolah_id', 'nip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gurus');
    }
};
