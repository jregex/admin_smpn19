<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Kelas extends Model
{
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Relasi: Kelas ini masuk ke dalam Sekolah mana
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    /**
     * Relasi: Kelas ini masuk ke dalam Jurusan apa (Bisa bernilai NULL jika SD/SMP)
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id')->withDefault([
            'kode_jurusan' => 'Umum',
            'nama_jurusan' => 'Umum',
        ]);
    }

    public function rombels()
    {
        return $this->hasMany(Rombel::class);
    }
}
