<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Guarded(['id'])]
class Rombel extends Model
{
    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function walikelas()
    {
        return $this->belongsTo(Guru::class);
    }

    public function tahunajaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function ekskuls(): BelongsToMany
    {
        return $this->belongsToMany(
            Ekskul::class,
            'ekskul_rombel',     // Nama tabel pivot di database Anda
            'rombel_id',         // Foreign key untuk model Rombel di tabel pivot
            'ekskul_id'         // Foreign key untuk model Ekskul di tabel pivot
        )->withPivot('tahun_ajaran_id')->withPivot('sekolah_id')->withTimestamps();
    }
}
