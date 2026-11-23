<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
class Soal extends Model
{
    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function pilihanJawabans()
    {
        return $this->hasMany(PilihanJawaban::class);
    }
}
