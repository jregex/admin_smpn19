<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Ujian extends Model
{
    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function pengawas()
    {
        return $this->belongsTo(Guru::class, 'pengawas_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    protected static function booted()
    {
        static::deleting(function ($ujian) {
            $ujian->soals->each(function ($soal) {
                $soal->pilihanJawabans()->delete();

                $soal->delete();
            });
            $ujian->each(function ($uj) {
                $uj->siswaUjians()->delete();
            });
        });

    }

    public function soals()
    {
        return $this->hasMany(Soal::class);
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function siswaUjians()
    {
        return $this->hasMany(SiswaUjian::class);
    }
}
