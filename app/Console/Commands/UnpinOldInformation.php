<?php

namespace App\Console\Commands;

use App\Models\Info;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('info:unpin-old')]
#[Description('Mengubah status is_pin menjadi false jika pengumuman sudah lebih dari 3 hari')]
class UnpinOldInformation extends Command
{
    public function handle()
    {
        $affectedRows = Info::where('is_pin', true)
            ->where('created_at', '<=', now()->subDays(3))
            ->update(['is_pin' => false]);

        if ($affectedRows > 0) {
            Log::info("CRON Sukses: Melumpuhkan status PIN pada {$affectedRows} pengumuman kuno.");
        } else {
            Log::info('CRON Selesai: Tidak ada pengumuman berumur > 3 hari yang tersemat (pinned).');
        }

        $this->info("Berhasil melepas pin pada {$affectedRows} pengumuman.");

        return Command::SUCCESS;
    }
}
