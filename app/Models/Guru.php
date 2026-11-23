<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Guarded(['id'])]
class Guru extends Model
{
    public function rombel(): HasOne
    {
        return $this->hasOne(Rombel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('filterSekolahDinamis', function (Builder $builder) {
            // 1. Pastikan hanya berjalan jika ada user yang login (aman untuk seeder / php artisan)
            if (auth()->check()) {
                $user = auth()->user();

                // 2. Jika user BUKAN superadmin, paksa query untuk menyaring sekolah_id
                if (! $user->hasRole('superadmin')) {
                    $builder->where('sekolah_id', $user->sekolah_id);
                }
            }
        });
    }
}
