<?php

namespace App\Jobs;

use App\Models\Ujian;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUjianNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $ujianId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ujian = Ujian::find($this->ujianId);
        if (! $ujian) {
            return;
        }

        // KUNCI UTAMA: Cari User (Siswa) yang rombelnya memiliki kelas_id sesuai ujian
        $tokens = User::whereNotNull('fcm_token')
            ->where('sekolah_id', $ujian->sekolah_id)
            ->whereHas('rombel', function ($query) use ($ujian) {
                $query->where('kelas_id', $ujian->kelas_id);
            })
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        // Kirim Payload ke Firebase
        $firebaseService = app(FirebaseService::class);
        $firebaseService->sendMulticast($tokens, "Ujian Dimulai: {$ujian->nama_ujian}", 'Ketuk notifikasi ini untuk memulai ujian.', [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'type' => 'mulai_ujian',
            'ujian_id' => (string) $ujian->id,
            'waktu_selesai' => (string) $ujian->waktu_selesai,
        ]);
    }
}
