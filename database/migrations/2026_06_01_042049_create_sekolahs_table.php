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
        Schema::create('sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('npsn')->nullable();
            $table->string('nama_sekolah');
            $table->string('email', 200)->nullable()->unique();
            $table->enum('jenjang', ['SMP', 'SMA', 'SMK']);
            $table->text('visi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('logo')->nullable()->default('default.png');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sekolahs');
    }
};
