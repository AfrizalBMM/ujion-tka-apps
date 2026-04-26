<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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

