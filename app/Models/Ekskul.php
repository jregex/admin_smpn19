<?php

namespace App\Models;

use App\Enum\Hari;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('ekskuls')]
#[Guarded(['id'])]
class Ekskul extends Model
{
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function pembina()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    protected $casts = [
        'hari' => Hari::class,
    ];

    public function rombels(): BelongsToMany
    {
        return $this->belongsToMany(
            Rombel::class,
            'ekskul_rombel',     // 1. Nama tabel pivot yang benar di database Anda
            'ekskul_id',         // 2. Foreign key untuk model Ekskul di tabel pivot
            'rombel_id'           // 3. Foreign key untuk model Siswa di tabel pivot
        )->withPivot('tahun_ajaran_id')->withTimestamps();
    }
}
