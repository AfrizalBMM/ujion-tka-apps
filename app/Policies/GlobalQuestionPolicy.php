<?php

namespace App\Policies;

use App\Models\GlobalQuestion;
use App\Models\Jenjang;
use App\Models\User;

class GlobalQuestionPolicy
{
    public function view(User $user, GlobalQuestion $question): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if (! $user->isGuru()) {
            return false;
        }

        // Extra safety: only allow viewing active questions.
        if (! $question->is_active) {
            return false;
        }

        $jenjangId = Jenjang::where('kode', $user->jenjang)->value('id');
        if (! $jenjangId) {
            return false;
        }

        return (int) $question->jenjang_id === (int) $jenjangId;
    }
}

