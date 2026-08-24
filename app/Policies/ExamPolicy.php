<?php

namespace App\Policies;

use App\Models\User;

class ExamPolicy
{
    /**
     * Determine whether the user can manage exams.
     */
    public function manage(User $user): bool
    {
        return $user->isSuperadmin();
    }
}
