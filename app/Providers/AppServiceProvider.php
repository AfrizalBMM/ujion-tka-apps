<?php

namespace App\Providers;

use App\Models\MapelPaket;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\GlobalQuestion;
use App\Models\User;
use App\Policies\GlobalQuestionPolicy;
use App\Policies\PaketSoalPolicy;
use App\Policies\SoalPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(PaketSoal::class, PaketSoalPolicy::class);
        Gate::policy(Soal::class, SoalPolicy::class);
        Gate::policy(GlobalQuestion::class, GlobalQuestionPolicy::class);

        Gate::define('manage-mapel-soal', function (User $user, MapelPaket $mapelPaket): bool {
            return $user->isSuperadmin() || ($user->isGuru() && $user->jenjang === $mapelPaket->paketSoal?->jenjang?->kode);
        });
    }
}
