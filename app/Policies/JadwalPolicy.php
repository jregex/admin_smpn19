<?php

namespace App\Policies;

use App\Models\Jadwal;
use App\Models\User;

class JadwalPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin_sekolah', 'guru']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin_sekolah']);
    }

    public function update(User $user, Jadwal $jadwal): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin_sekolah']);
    }

    public function delete(User $user, Jadwal $jadwal): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin_sekolah']);
    }
}
