<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
class TahunAjaran extends Model
{
    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function rombel()
    {
        return $this->hasMany(Rombel::class);
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class);
    }
}
