<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
class PilihanJawaban extends Model
{
    protected $casts = ['is_kunci' => 'boolean'];

    public function soal()
    {
        return $this->belongsTo(Soal::class);
    }
}
