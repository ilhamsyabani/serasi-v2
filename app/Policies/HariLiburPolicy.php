<?php

namespace App\Policies;

use App\Models\HariLibur;
use App\Models\User;

class HariLiburPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->kode === 'admin_it';
    }

    public function create(User $user): bool
    {
        return $user->role?->kode === 'admin_it';
    }

    public function update(User $user, HariLibur $hariLibur): bool
    {
        return $user->role?->kode === 'admin_it';
    }

    public function delete(User $user, HariLibur $hariLibur): bool
    {
        return $user->role?->kode === 'admin_it';
    }
}
