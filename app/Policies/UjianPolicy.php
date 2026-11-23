<?php

namespace App\Policies;

use App\Models\Ujian;
use App\Models\User;

class UjianPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['superadmin', 'admin_sekolah', 'guru'])) {
            return true;
        }

        return null; // Teruskan ke method spesifik jika bukan admin
    }

    /**
     * Menentukan apakah user dapat melihat daftar ujian (Index).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-ujian');
    }

    /**
     * Menentukan apakah user dapat melihat detail ujian spesifik.
     */
    public function view(User $user, Ujian $ujian): bool
    {
        if (! $user->hasPermissionTo('view-ujian')) {
            return false;
        }

        // Jika Guru, hanya boleh melihat ujian miliknya sendiri
        if ($user->hasRole('guru')) {

            return $user->guru?->id === $ujian->guru_id;
        }

        return $user->sekolah_id === $ujian->sekolah_id;
    }

    /**
     * Menentukan apakah user dapat membuat ujian baru.
     */
    public function create(User $user, Ujian $ujian): bool
    {
        if (! $user->hasPermissionTo('create-ujian')) {
            return false;
        }
        if ($user->hasRole('guru')) {
            return $user->guru->id === $ujian->guru_id;
        }

        return $user->sekolah_id === $ujian->sekolah_id;

        /* return $user->hasPermissionTo('create-ujian'); */
    }

    /**
     * Menentukan apakah user dapat memperbarui/mengedit data ujian.
     */
    public function update(User $user, Ujian $ujian): bool
    {
        if (! $user->hasPermissionTo('edit-ujian')) {
            return false;
        }

        // Guru hanya boleh mengedit ujian yang ia buat sendiri
        if ($user->hasRole('guru')) {
            return $user->guru->id === $ujian->guru_id;
        }

        return $user->sekolah_id === $ujian->sekolah_id;
    }

    /**
     * Menentukan apakah user dapat menghapus ujian.
     */
    public function delete(User $user, Ujian $ujian): bool
    {
        if (! $user->hasPermissionTo('delete-ujian')) {
            return false;
        }

        // Guru hanya boleh menghapus ujian yang ia buat sendiri
        if ($user->hasRole('guru')) {

            return $user->guru?->id === $ujian->guru_id;
        }

        return $user->sekolah_id === $ujian->sekolah_id;
    }
}
