<?php

namespace App\Http\Controllers\Api;

use App\Exports\NilaiSiswaExport;
use App\Http\Controllers\Controller;
use App\Models\SiswaJawabanUjian;
use App\Models\SiswaUjian;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\NilaiUjianService;
use App\Services\UjianService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UjianController extends Controller
{
    public function __construct(protected UjianService $ujian_service) {}

    public function getUjian(Request $request)
    {
        $siswa = $request->user()->siswa;
        $kelasId = $siswa->rombel->kelas_id;
        if (! $kelasId) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan. Pastikan siswa sudah masuk rombel.',
            ], 404);
        }
        $ujians = Ujian::with(['mapel', 'kelas', 'pengawas', 'soals.pilihanJawabans', 'siswaUjians' => function ($query) use ($siswa) {
            $query->where('siswa_id', $siswa->id);
        }])->where('kelas_id', $kelasId)
            ->orderBy('waktu_mulai', 'asc')
            ->get();
        Carbon::setLocale('id');
        $kategoriHari = $ujians->groupBy(function ($ujian) {
            return Carbon::parse($ujian->waktu_mulai)->translatedFormat('l');
        })->map(function ($items) {
            return $items->map(function ($item) {
                $partisipasiSiswa = $item->siswaUjians->first();

                $totalSoal = $item->soals->count();
                $jawabanBenar = $partisipasiSiswa ? SiswaJawabanUjian::where('siswa_id', $partisipasiSiswa->siswa_id)->where('ujian_id', $item->id)->where('is_benar', true)->count() : 0;
                $jawabanSalah = $partisipasiSiswa ? ($totalSoal - $jawabanBenar) : 0;

                return [
                    'id' => $item->id,
                    'nama_ujian' => $item->nama_ujian,
                    'nama_kelas' => $item->kelas->nama_kelas,
                    'kelas_id' => $item->kelas_id,
                    'nama_mapel' => $item->mapel->nama_mapel,
                    'pengawas' => $item->pengawas->nama,
                    'jam_mulai' => $item->waktu_mulai->toIso8601String(),
                    'jam_selesai' => $item->waktu_selesai->toIso8601String(),
                    'status' => $item->status,
                    'status_siswa' => $partisipasiSiswa ? $partisipasiSiswa->status : 'belum_mulai',
                    'nilai_siswa' => $partisipasiSiswa ? (int) $partisipasiSiswa->nilai : null,
                    'total_soal' => $totalSoal,
                    'jawaban_benar' => $jawabanBenar,
                    'jawaban_salah' => $jawabanSalah,
                    'list_soal' => $item->soals->map(function ($soal) {
                        return [
                            'id' => $soal->id,
                            'ujian_id' => $soal->ujian_id,
                            'pertanyaan' => $soal->teks_soal,
                            'kunci_jawaban' => $soal->kunci_jawaban,
                            'gambar' => $soal->gambar ? asset('gambar/images/soal/'.$soal->gambar) : null,

                            'pilihan' => $soal->pilihanJawabans->map(function ($pilihan) {
                                return [
                                    'id' => $pilihan->id,
                                    'opsi' => $pilihan->opsi, // Berisi 'A', 'B', 'C', dll
                                    'teks_pilihan' => $pilihan->teks_pilihan,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            });
        });

        return response()->json([
            'success' => true,
            'daftar_hari' => $kategoriHari->keys(),
            'data_ujian' => $kategoriHari,
        ], 200);
    }

    public function exportNilaiSiswa($id)
    {
        $ujian = Ujian::with(['kelas', 'mapel'])->findOrFail($id);
        Gate::authorize('view', $ujian);

        if ($ujian->status !== 'SELESAI') {
            return back()->with('warning', 'Nilai siswa hanya dapat diexport setelah ujian selesai.');
        }

        $daftarNilai = app(NilaiUjianService::class)->daftarNilai($ujian);
        $namaKelas = Str::slug($ujian->kelas->nama_kelas ?? 'kelas');
        $namaUjian = Str::slug($ujian->nama_ujian);
        $filename = "nilai-ujian-{$namaUjian}-{$namaKelas}.xlsx";

        return Excel::download(new NilaiSiswaExport($ujian, $daftarNilai), $filename);
    }

    public function aktivasiUjian(Request $request, $id)
    {
        $ujian = Ujian::findOrFail($id);

        $waktuMulai = now();
        $waktuSelesai = (clone $waktuMulai)->addMinutes($ujian->durasi);
        if ($waktuMulai->lt($ujian->waktu_mulai)) {
            // Hitung selisih waktu untuk memberikan info yang jelas kepada guru
            $selisih = $waktuMulai->diffForHumans($ujian->waktu_mulai, [
                'parts' => 2,
                'join' => ', ',
            ]);

            return back()->with('warning', 'Ujian belum bisa dimulai! Waktu pelaksanaan kurang '.$selisih);
        }
        // Update di database lokal Laravel
        $update = $ujian->update([
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'status' => 'BERLANGSUNG',
        ]);

        if ($update) {
            $this->ujian_service->aktivasiUjianRealtime($ujian, auth()->user()->sekolah->nama_sekolah);

            return back()->with('success', 'Anda sedang memulai ujian');
        } else {

            return back()->with('error', 'Gagal memulai ujian');
        }

        /* return response()->json([ */
        /*     'success' => true, */
        /*     'message' => 'Ujian berhasil diaktifkan. Gawai siswa otomatis terkunci ke lembar ujian.', */
        /*     'data' => $ujian, */
        /* ]); */
    }

    public function stopUjian(Request $request, $id)
    {
        $ujian = Ujian::findOrFail($id);

        $update = $ujian->update([
            'status' => 'SELESAI',
            'is_active' => false,
        ]);

        if ($update) {
            $this->ujian_service->matikanUjianRealtime($ujian);

            return back()->with('success', 'Anda telah menyelesaikan ujian');
        } else {

            return back()->with('error', 'Gagal menghentikan ujian');
        }

    }

    public function perpanjangWaktu(Request $request, $id)
    {
        $request->validate(['menit_tambahan' => 'required|integer']);

        $ujian = Ujian::findOrFail($id);
        $waktuSelesaiBaru = Carbon::parse($ujian->waktu_selesai)->addMinutes($request->menit_tambahan);

        $ujian->update([
            'waktu_selesai' => $waktuSelesaiBaru,
            'durasi' => $ujian->durasi + $request->menit_tambahan,
        ]);

        // Panggil Service Anda di sini
        $this->ujian_service->perpanjangUjianRealtime($ujian);

        return response()->json([
            'success' => true,
            'message' => "Waktu ujian diperpanjang {$request->menit_tambahan} menit.",
        ]);
    }

    public function alihkanKeOffline($id)
    {
        $ujian = Ujian::findOrFail($id);
        $ujian->update(['status' => 'OFFLINE', 'is_active' => false]);

        // Panggil Service Anda di sini
        $this->ujian_service->alihkanOfflineRealtime($ujian);

        return response()->json([
            'success' => true,
            'message' => 'Ujian dialihkan ke offline kertas.',
        ]);
    }

    public function simpanJawaban(Request $request)
    {
        $request->validate([
            'ujian_id' => ['required', 'exists:ujians,id'],
            'soal_id' => [
                'required',
                Rule::exists('soals', 'id')->where(
                    fn ($query) => $query->where('ujian_id', $request->integer('ujian_id'))
                ),
            ],
            'jawaban_siswa' => ['nullable', 'string', 'max:2'],
        ]);

        $siswa = $request->user()->siswa;
        $ujian = Ujian::findOrFail($request->integer('ujian_id'));
        $kelasId = $siswa->rombel->kelas_id;

        if ((int) $ujian->kelas_id !== (int) $kelasId) {
            return response()->json([
                'success' => false,
                'message' => 'Ujian ini bukan untuk kelas Anda.',
            ], 403);
        }

        if ($ujian->status === 'OFFLINE') {
            return response()->json([
                'success' => false,
                'message' => 'Ujian ini dialihkan ke mode offline kertas.',
            ], 400);
        }

        $soal = Soal::findOrFail($request->integer('soal_id'));
        $isBenar = strtoupper($soal->kunci_jawaban) === strtoupper((string) $request->input('jawaban_siswa')) ? 1 : 0;

        SiswaJawabanUjian::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'ujian_id' => $ujian->id,
                'soal_id' => $soal->id,
            ],
            [
                'jawaban_siswa' => $request->input('jawaban_siswa'),
                'is_benar' => $isBenar === 1,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil disimpan.',
            'is_benar' => $isBenar,
        ]);
    }

    public function selesaiUjian(Request $request)
    {
        $request->validate([
            'ujian_id' => ['required', 'exists:ujians,id'],
            'daftar_jawaban' => ['required', 'array'],
            'daftar_jawaban.*.soal_id' => [
                'required',
                Rule::exists('soals', 'id')->where(
                    fn ($query) => $query->where('ujian_id', $request->integer('ujian_id'))
                ),
            ],
            'daftar_jawaban.*.jawaban_siswa' => ['nullable', 'string', 'max:2'],
        ]);

        $siswa = $request->user()->siswa;
        $siswaId = $siswa->id;
        $ujian = Ujian::findOrFail($request->integer('ujian_id'));
        $kelasId = $siswa->rombel->kelas_id;

        if ((int) $ujian->kelas_id !== (int) $kelasId) {
            return response()->json([
                'success' => false,
                'message' => 'Ujian ini bukan untuk kelas Anda.',
            ], 403);
        }

        if ($ujian->status === 'OFFLINE') {
            return response()->json([
                'success' => false,
                'message' => 'Ujian ini dialihkan ke mode offline kertas.',
            ], 400);
        }

        if (in_array($ujian->status, ['BELUM MULAI', 'DITUNDA'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Ujian belum dimulai atau sedang ditunda.',
            ], 400);
        }

        $sudahSelesai = SiswaUjian::where('siswa_id', $siswaId)
            ->where('ujian_id', $ujian->id)
            ->where('status', 'selesai')
            ->exists();

        if ($sudahSelesai) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengumpulkan ujian ini sebelumnya.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $jawabanBenar = 0;

            foreach ($request->daftar_jawaban as $item) {
                $soal = Soal::findOrFail($item['soal_id']);

                // 1. Validasi Kunci Jawaban (Gunakan kolom kunci_jawaban sesuai migrasi)
                $isBenar = (strtoupper($soal->kunci_jawaban) === strtoupper($item['jawaban_siswa'] ?? '')) ? 1 : 0;

                if ($isBenar === 1) {
                    $jawabanBenar++;
                }

                // 2. Simpan atau update jawaban siswa
                SiswaJawabanUjian::updateOrCreate(
                    [
                        'siswa_id' => $siswaId,
                        'ujian_id' => $ujian->id,
                        'soal_id' => $item['soal_id'],
                    ],
                    [
                        'jawaban_siswa' => $item['jawaban_siswa'],
                        'is_benar' => $isBenar == 1 ? true : false,
                    ]
                );
            }

            // 3. Hitung total soal
            $totalSoal = Soal::where('ujian_id', $ujian->id)->count();

            // 4. Hitung Rumus Nilai (dibulatkan ke integer)
            $nilaiAkhir = $totalSoal > 0 ? (int) round(($jawabanBenar / $totalSoal) * 100) : 0;

            // 5. KOREKSI AMAN: Gunakan updateOrCreate untuk menghindari Error 500 firstOrFail
            SiswaUjian::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'ujian_id' => $ujian->id,
                ],
                [
                    'waktu_selesai_kerja' => now(),
                    'nilai' => $nilaiAkhir,
                    'status' => 'selesai',
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ujian berhasil dikumpulkan.',
                'nilai' => $nilaiAkhir,
                'benar' => $jawabanBenar,
                'salah' => $totalSoal - $jawabanBenar,
                'mapel' => $ujian->mapel?->nama_mapel,
                'nama_ujian' => $ujian->nama_ujian,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            // Mengembalikan pesan error asli agar terbaca jelas oleh Dio di Flutter
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cetakSoalSiswa($id)
    {
        // Ambil relasi soal dan pilihan jawaban (urutkan abjad A-D)
        $ujian = Ujian::with(['soals.pilihan_jawabans'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.lembar_soal', [
            'ujian' => $ujian,
            'title' => 'LEMBAR SOAL UJIAN - '.strtoupper($ujian->nama_ujian),
            'tipe' => 'siswa',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Soal_Siswa_'.str_replace(' ', '_', $ujian->nama_ujian).'.pdf');
    }

    public function cetakKunciGuru($id)
    {
        $ujian = Ujian::with(['soals.pilihan_jawabans'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.lembar_soal', [
            'ujian' => $ujian,
            'title' => 'KUNCI JAWABAN - '.strtoupper($ujian->nama_ujian),
            'tipe' => 'guru',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Kunci_Guru_'.str_replace(' ', '_', $ujian->nama_ujian).'.pdf');
    }
}
