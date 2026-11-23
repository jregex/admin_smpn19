<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Guarded(['id'])]
class Sekolah extends Model
{
    public function jurusans(): HasMany
    {
        return $this->hasMany(Jurusan::class, 'sekolah_id');
    }

    /**
     * Relasi: Satu Sekolah memiliki banyak Kelas
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'sekolah_id');
    }

    public function gurus(): HasMany
    {
        return $this->hasMany(Guru::class, 'sekolah_id');
    }

    public function tahunAjarans(): HasMany
    {
        return $this->hasMany(TahunAjaran::class, 'sekolah_id');
    }

    public function tahunAjaranAktif(): HasOne
    {
        return $this->hasOne(TahunAjaran::class, 'sekolah_id')
            ->where('is_aktif', true); // Filter otomatis hanya yang aktif
    }

    public function info(): HasMany
    {
        return $this->hasMany(Info::class);
    }
}
