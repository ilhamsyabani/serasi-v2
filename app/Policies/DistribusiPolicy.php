<?php

namespace App\Policies;

use App\Models\Distribusi;
use App\Models\Permohonan;
use App\Models\User;

class DistribusiPolicy
{
    /** Hanya Ketua Tim yang boleh distribusi/reassign. */
    public function create(User $user, ?Permohonan $permohonan = null): bool
    {
        return $user->isKetuaTim();
    }

    /** Reassignment hanya oleh Ketua Tim. */
    public function reassign(User $user, Distribusi $distribusi): bool
    {
        return $user->isKetuaTim() && $distribusi->ketua_tim_id === $user->id;
    }
}
