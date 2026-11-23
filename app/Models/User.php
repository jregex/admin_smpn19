<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Guarded(['id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    protected $with = ['role', 'sekolah'];

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    public function isSiswa(): bool
    {
        return $this->siswa()->exists();
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'user_id', 'id');
    }

    public function isGuru(): bool
    {
        return $this->guru()->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    public function scopeFromSekolah(Builder $query, string $sekolah): Builder
    {
        return $query->whereHas('sekolah', fn ($q) => $q->where('nama_sekolah', $sekolah));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke tabel Role
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Relasi ke tabel Sekolah
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    // Helper Method untuk mengecek role dengan cepat
    public function hasRole(string $roleName): bool
    {
        return $this->role?->role === $roleName;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        return in_array($this->role?->role, $roleNames);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function infos(): HasMany
    {
        return $this->hasMany(Info::class, 'user_id');
    }
}
