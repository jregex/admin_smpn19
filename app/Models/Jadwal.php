<?php

namespace App\Models;

use App\Enum\Hari;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
class Jadwal extends Model
{
    protected $casts = [
        'hari' => Hari::class,
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rombel()
    {
        return $this->belongsTo(Rombel::class);
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    public function tahunajaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }
}
