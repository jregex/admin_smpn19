<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EkskulResource;
use App\Http\Resources\InfoResource;
use App\Http\Resources\JadwalResource;
use App\Models\Ekskul;
use App\Models\Info;
use App\Models\Jadwal;
use App\Models\TahunAjaran;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataController extends Controller
{
    public function jadwals(Request $request)
    {
        try {
            $tahun_ajaran = TahunAjaran::where('is_aktif', true)->first();

            $jadwalRaw = Jadwal::with(['mapel', 'guru', 'rombel'])
                ->where('sekolah_id', $request->user()->sekolah_id)
                ->where('tahun_ajaran_id', $tahun_ajaran->id)
                ->where('rombel_id', Auth::user()->siswa->rombel_id)
                ->orderBy('jam_mulai', 'asc')
                ->get();

            // Transformasi data mentah menggunakan JadwalResource
            $jadwalTransformed = JadwalResource::collection($jadwalRaw);

            // Kelompokkan hasil transformasi berdasarkan Enum hari
            $urutanHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $jadwalGrouped = [];

            foreach ($urutanHari as $hariName) {
                // Memeriksa kesesuaian nilai Enum (string) pada model asal
                $itemHari = $jadwalRaw->filter(function ($jadwal) use ($hariName) {
                    return $jadwal->hari->value === $hariName || $jadwal->hari->name === $hariName;
                });

                if ($itemHari->isNotEmpty()) {
                    // Ambil hasil Resource yang sudah di-transform sesuai ID jadwal yang cocok
                    $filteredIds = $itemHari->pluck('id')->toArray();

                    $jadwalGrouped[$hariName] = collect($jadwalTransformed)
                        ->whereIn('id', $filteredIds)
                        ->values()
                        ->all();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal pelajaran ditemukan',
                'data' => $jadwalGrouped,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat jadwal: '.$e->getMessage(),
            ], 500);
        }
    }

    public function infos(Request $request)
    {
        try {
            $infos = Info::with('pembuat')->where('sekolah_id', $request->user()->sekolah_id)
                ->whereIn('target_audiens', ['semua', 'siswa']) // Sesuaikan jika aplikasi digunakan oleh siswa
                ->orderBy('is_pin', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['data' => InfoResource::collection($infos)], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat pengumuman: '.$e->getMessage(),
            ], 500);

        }

    }

    public function ekskuls(Request $request)
    {

        $ekskul = Ekskul::whereHas('rombels', function ($query) use ($request) {
            $query->where('rombel_id', $request->user()->siswa->rombel_id);
        })->with('pembina:id,nama')->get();

        return response()->json([
            'status' => 'success',
            'data' => EkskulResource::collection($ekskul),
        ], 200);
    }

    public function detailInfo(Request $request, $id)
    {
        try {
            $info = Info::with('pembuat')->where('id', $id)->where('sekolah_id', $request->user()->sekolah_id)->first();

            return response()->json(['data' => new InfoResource($info)], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal memuat detail info : '.$e->getMessage()]);
        }
    }
}
