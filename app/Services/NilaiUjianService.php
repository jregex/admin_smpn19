<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\SiswaJawabanUjian;
use App\Models\SiswaUjian;
use App\Models\Ujian;

class NilaiUjianService
{
    /**
     * Ambil daftar nilai seluruh siswa pada kelas ujian.
     *
     * @return array<int, array{nisn: string, nama: string, nilai: int|null, benar: int|null, salah: int|null, status: string}>
     */
    public function daftarNilai(Ujian $ujian): array
    {
        $partisipasi = SiswaUjian::query()
            ->where('ujian_id', $ujian->id)
            ->get()
            ->keyBy('siswa_id');

        $totalSoal = $ujian->soals()->count();

        $jawabanBenar = SiswaJawabanUjian::query()
            ->selectRaw('siswa_id, COUNT(*) as jumlah_benar')
            ->where('ujian_id', $ujian->id)
            ->where('is_benar', true)
            ->groupBy('siswa_id')
            ->pluck('jumlah_benar', 'siswa_id');

        return Siswa::query()
            ->where('sekolah_id', $ujian->sekolah_id)
            ->whereHas('rombel', fn ($query) => $query->where('kelas_id', $ujian->kelas_id))
            ->orderBy('nama')
            ->get()
            ->map(function (Siswa $siswa) use ($partisipasi, $jawabanBenar, $totalSoal) {
                $dataUjian = $partisipasi->get($siswa->id);
                $benar = $dataUjian ? (int) $jawabanBenar->get($siswa->id, 0) : null;

                return [
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama,
                    'nilai' => $dataUjian?->nilai,
                    'benar' => $benar,
                    'salah' => $benar !== null ? $totalSoal - $benar : null,
                    'status' => $dataUjian?->status ?? 'tidak_mengikuti',
                ];
            })
            ->values()
            ->toArray();
    }
}
