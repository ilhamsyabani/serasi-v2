<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Hanya Admin IT yang boleh mengelola user internal. */
    public function manage(User $user): bool
    {
        return $user->isAdminIt();
    }
}
