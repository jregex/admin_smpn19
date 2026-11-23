<?php

namespace App\Http\Middleware;

use App\Models\TahunAjaran;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetTahunAjaran
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $taAktif = Cache::rememberForever('global_ta_aktif', function () {
            // 1. Cari yang dicentang aktif secara manual
            $model = TahunAjaran::where('is_aktif', true)->first();

            // 2. Fallback: Deteksi otomatis berbasis tanggal jika tidak ada yang aktif
            if (! $model) {
                $sekarang = Carbon::now();
                $stringTA = ($sekarang->month >= 7)
                    ? $sekarang->year.'/'.($sekarang->year + 1)
                    : ($sekarang->year - 1).'/'.$sekarang->year;

                $model = TahunAjaran::where('tahun', $stringTA)->first()
                    ?? TahunAjaran::orderBy('tanggal_mulai', 'desc')->first();
            }

            // Return berupa array murni, bukan objek model Eloquent
            return $model ? [
                'id' => $model->id,
                'tahun' => $model->tahun,
            ] : null;
        });

        if ($taAktif) {
            // Mengakses properti menggunakan sintaks array [] karena data bukan objek lagi
            $request->session()->put([
                'tahun_ajaran_id' => $taAktif['id'],
                'tahun_ajaran_nama' => $taAktif['tahun'],
            ]);
        }

        return $next($request);
    }
}
