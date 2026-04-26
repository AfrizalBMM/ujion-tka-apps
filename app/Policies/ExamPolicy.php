<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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

