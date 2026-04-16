<?php

namespace App\Policies;

use App\Models\PaketSoal;
use App\Models\User;

class PaketSoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isGuru();
    }

    public function view(User $user, PaketSoal $paketSoal): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isGuru() && ($user->jenjang === $paketSoal->jenjang?->kode);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, PaketSoal $paketSoal): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, PaketSoal $paketSoal): bool
    {
        return $user->isSuperadmin();
    }

    public function toggleAktif(User $user, PaketSoal $paketSoal): bool
    {
        return $user->isSuperadmin();
    }
}
