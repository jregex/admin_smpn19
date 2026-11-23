<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class SiswaUjian extends Model
{
    protected $casts = [
        'waktu_mulai_kerja' => 'datetime',
        'waktu_selesai_kerja' => 'datetime',
        'nilai' => 'integer',
    ];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    // RELASI: Record tracking ini dimiliki oleh satu Siswa
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
