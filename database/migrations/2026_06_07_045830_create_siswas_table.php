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
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolahs')->onDelete('cascade');
            $table->foreignId('rombel_id')->constrained('rombels')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nisn', 20);
            $table->string('nama', 150);
            $table->enum('agama', ['Islam', 'Kristen', 'Budha', 'Hindu', 'Kong Hu Cu']);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir')->default('-');
            $table->string('no_telp', 15)->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->unique(['sekolah_id', 'nisn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
