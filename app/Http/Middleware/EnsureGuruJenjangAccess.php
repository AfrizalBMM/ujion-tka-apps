<?php

namespace App\Http\Middleware;

use App\Models\MapelPaket;
use App\Models\PaketSoal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruJenjangAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isGuru()) {
            abort(403);
        }

        /** @var PaketSoal|null $paket */
        $paket = $request->route('paket');
        /** @var MapelPaket|null $mapel */
        $mapel = $request->route('mapel');

        if ($paket && $mapel && $mapel->paket_soal_id !== $paket->id) {
            abort(404);
        }

        $jenjangKode = $mapel?->paketSoal?->jenjang?->kode ?? $paket?->jenjang?->kode;

        if ($jenjangKode && $jenjangKode !== $user->jenjang) {
            abort(403);
        }

        return $next($request);
    }
}
