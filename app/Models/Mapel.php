<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Mapel extends Model
{
    protected $with = ['jurusan', 'sekolah'];

    // Relasi ke tabel Sekolah
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    /**
     * Relasi: Kelas ini masuk ke dalam Jurusan apa (Bisa bernilai NULL jika SD/SMP)
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }
}
