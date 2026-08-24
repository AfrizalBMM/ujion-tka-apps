<?php

namespace App\Policies;

use App\Models\User;

class MaterialPolicy
{
    /**
     * Determine whether the user can manage materials.
     */
    public function manage(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can delete all materials.
     */
    public function deleteAll(User $user): bool
    {
        return $user->isSuperadmin();
    }
}
