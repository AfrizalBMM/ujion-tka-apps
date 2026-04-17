<?php

namespace App\Policies;

use App\Models\MapelPaket;
use App\Models\Soal;
use App\Models\User;

class SoalPolicy
{
    public function viewAny(User $user, MapelPaket $mapelPaket): bool
    {
        return $user->isSuperadmin() || ($user->isGuru() && $user->jenjang === $mapelPaket->paketSoal?->jenjang?->kode);
    }

    public function view(User $user, Soal $soal): bool
    {
        return $user->isSuperadmin() || ($user->isGuru() && $user->jenjang === $soal->mapelPaket?->paketSoal?->jenjang?->kode);
    }

    public function create(User $user, MapelPaket $mapelPaket): bool
    {
        return $user->isSuperadmin()
            || ($user->isGuru() && $mapelPaket->paketSoal?->isManagedByGuru($user));
    }

    public function update(User $user, Soal $soal): bool
    {
        return $user->isSuperadmin()
            || ($user->isGuru() && $soal->mapelPaket?->paketSoal?->isManagedByGuru($user));
    }

    public function delete(User $user, Soal $soal): bool
    {
        return $this->update($user, $soal);
    }
}
