<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
class SiswaJawabanUjian extends Model
{
    protected $casts = ['is_benar' => 'boolean'];

    /**
     * Boot fungsi otomatis (Eloquent Event)
     */
    protected static function booted()
    {
        // Event 'saving' berjalan otomatis saat data akan dibuat (create) maupun diperbarui (update)
        static::saving(function ($jawabanSiswa) {
            // 1. Ambil data soal terkait beserta kunci jawabannya
            $soal = Soal::find($jawabanSiswa->soal_id);

            if ($soal) {
                // 2. Bersihkan spasi dan samakan format huruf menjadi kapital (A, B, C, D, E)
                $kunci = strtoupper(trim($soal->kunci_jawaban));
                $pilihanSiswa = strtoupper(trim($jawabanSiswa->jawaban_siswa));

                // 3. Bandingkan jawaban. Jika cocok, set true (1), jika salah/kosong set false (0)
                $jawabanSiswa->is_benar = (! empty($pilihanSiswa) && $pilihanSiswa === $kunci);
            } else {
                $jawabanSiswa->is_benar = false;
            }
        });
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    public function soal()
    {
        return $this->belongsTo(Soal::class);
    }
}
