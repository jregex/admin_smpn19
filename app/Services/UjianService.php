<?php

namespace App\Services;

use Kreait\Firebase\Contract\Database;

class UjianService
{
    public function __construct(protected Database $database, protected FirebaseService $firebase_service)
    {
    }

    public function aktivasiUjianRealtime($ujian, $nama_sekolah)
    {
        $this->database->getReference('ujians/kelas_'.$ujian->kelas_id)
            ->set([
                'ujian_id' => (string) $ujian->id,
                'nama_ujian' => $ujian->nama_ujian,
                'nama_sekolah' => $nama_sekolah,
                'waktu_selesai' => $ujian->waktu_selesai->toIso8601String(),
                'status' => $ujian->status,
            ]);
    }

    public function perpanjangUjianRealtime($ujian)
    {
        $this->database->getReference('ujians/kelas_'.$ujian->kelas_id)
            ->update([
                'waktu_selesai' => $ujian->waktu_selesai->toIso8601String(),
            ]);
    }

    public function tundaUjianRealtime($ujian)
    {
        $this->database->getReference('ujians/kelas_'.$ujian->kelas_id)
            ->update([
                'status' => $ujian->status,
            ]);
    }

    public function alihkanOfflineRealtime($ujian)
    {
        $this->database->getReference('ujians/kelas_'.$ujian->kelas_id)
            ->update([
                'status' => $ujian->status,
            ]);
    }

    public function matikanUjianRealtime($ujian)
    {
        $this->database->getReference('ujians/kelas_'.$ujian->kelas_id)
            ->update([
                'status' => $ujian->status,
            ]);
    }
}
