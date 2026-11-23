<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Jurusan extends Model
{
    /**
     * Relasi: Jurusan ini milik/berada di Sekolah mana
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * Relasi: Satu Jurusan menaungi banyak Kelas
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'jurusan_id');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }
}
