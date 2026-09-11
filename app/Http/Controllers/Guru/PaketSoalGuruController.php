<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PaketSoal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaketSoalGuruController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorize('viewAny', PaketSoal::class);

        $paketSoals = PaketSoal::query()
            ->with(['jenjang', 'mapelPakets'])
            ->whereHas('jenjang', fn ($query) => $query->where('kode', $user->jenjang))
            ->latest()
            ->get()
            ->map(fn (PaketSoal $paket) => [
                'id' => $paket->id,
                'nama' => $paket->nama,
                'tahun_ajaran' => $paket->tahun_ajaran,
                'is_active' => (bool) $paket->is_active,
                'jenjang_kode' => $paket->jenjang?->kode,
                'mapel_labels' => $paket->mapelPakets->map(fn ($mapel) => $mapel->nama_label)->values()->all(),
            ])
            ->values();

        return Inertia::render('Guru/PaketSoal/Index', [
            'paketSoals' => $paketSoals,
            'jenjang' => $user->jenjang,
        ]);
    }

    public function show(Request $request, PaketSoal $paket): Response
    {
        $this->authorize('view', $paket);

        $paket->load([
            'jenjang',
            'mapelPakets.teksBacaans',
            'mapelPakets.soals.pilihanJawabans',
            'mapelPakets.soals.pasanganMenjodohkans',
            'exams' => fn ($q) => $q->with('examMapelTokens.mapelPaket')
                ->where('status', 'terbit')
                ->where('is_active', true)
                ->orderByDesc('tanggal_terbit'),
        ]);

        $canManage = $paket->isManagedByGuru($request->user());

        $mapelPakets = $paket->mapelPakets
            ->map(fn ($mapel) => [
                'id' => $mapel->id,
                'nama_label' => $mapel->nama_label,
                'jumlah_soal' => $mapel->jumlah_soal,
                'durasi_menit' => $mapel->durasi_menit,
                'urutan' => $mapel->urutan,
                'is_survey' => $mapel->isSurvey(),
                'soals_count' => $mapel->soals->count(),
                'soals' => $mapel->soals->take(5)
                    ->map(fn ($soal) => [
                        'id' => $soal->id,
                        'nomor_soal' => $soal->nomor_soal,
                        'tipe_label' => str($soal->tipe_soal)->replace('_', ' ')->headline()->toString(),
                        'pertanyaan_limited' => Str::limit(strip_tags($soal->pertanyaan), 120),
                        'dimensi' => $soal->dimensi,
                        'subdimensi' => $soal->subdimensi,
                    ])
                    ->values(),
            ])
            ->values();

        $exams = $paket->exams
            ->map(fn ($exam) => [
                'id' => $exam->id,
                'judul' => $exam->judul,
                'tanggal_terbit' => $exam->tanggal_terbit?->format('d M Y'),
                'max_peserta' => $exam->max_peserta,
                'tokens' => $exam->examMapelTokens
                    ->map(fn ($mt) => [
                        'id' => $mt->id,
                        'token' => $mt->token,
                        'mapel_label' => $mt->mapelPaket?->nama_label ?? 'Mapel',
                    ])
                    ->values(),
            ])
            ->values();

        return Inertia::render('Guru/PaketSoal/Show', [
            'paket' => [
                'id' => $paket->id,
                'nama' => $paket->nama,
                'tahun_ajaran' => $paket->tahun_ajaran,
                'jenjang_kode' => $paket->jenjang?->kode,
            ],
            'canManage' => $canManage,
            'mapelPakets' => $mapelPakets,
            'exams' => $exams,
        ]);
    }
}
