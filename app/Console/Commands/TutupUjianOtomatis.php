<?php

namespace App\Console\Commands;

use App\Models\Ujian;
use App\Services\UjianService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('ujian:tutup-otomatis')]
#[Description('Mengubah status ujian menjadi selesai secara otomatis jika melewati waktu_selesai')]
class TutupUjianOtomatis extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ujianService = app(UjianService::class);
        $ujianExpired = Ujian::where('status', 'BERLANGSUNG')
            ->where('waktu_selesai', '<=', now())
            ->get();

        if ($ujianExpired->isEmpty()) {
            Log::info('Tidak ada ujian yang perlu ditutup saat ini.');

            return 0;
        }

        foreach ($ujianExpired as $ujian) {
            // 2. Perbarui status di Database Utama (MySQL/PostgreSQL)
            $ujian->update([
                'status' => 'SELESAI', // atau sesuaikan dengan enum status Anda, misal 'SELESAI'
            ]);

            // 3. Manfaatkan UjianService Anda untuk sinkronisasi otomatis ke Firebase
            $ujianService->matikanUjianRealtime($ujian);

            Log::info("Ujian ID {$ujian->id} berhasil diubah ke status SELESAI di MySQL & Firebase.");

        }

        return 0;
    }
}
