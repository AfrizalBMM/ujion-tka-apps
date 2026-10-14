<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExamGuideController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $exams = Exam::query()
            ->with(['paketSoal.jenjang', 'examMapelTokens.mapelPaket'])
            ->where('status', 'terbit')
            ->where('is_active', true)
            ->whereHas('paketSoal.jenjang', fn ($query) => $query->where('kode', $user->jenjang))
            ->orderByDesc('tanggal_terbit')
            ->get()
            ->map(fn (Exam $exam) => [
                'id' => $exam->id,
                'judul' => $exam->judul,
                'paket_nama' => $exam->paketSoal?->nama ?? '-',
                'tanggal_terbit' => $exam->tanggal_terbit?->format('d M Y H:i'),
                'tokens' => $exam->examMapelTokens
                    ->map(fn ($mt) => [
                        'id' => $mt->id,
                        'token' => $mt->token,
                        'mapel_label' => $mt->mapelPaket?->nama_label ?? 'Mapel',
                    ])
                    ->values(),
            ])
            ->values();

        return Inertia::render('Guru/PanduanUjian', [
            'exams' => $exams,
            'loginUrl' => route('siswa.login'),
        ]);
    }
}
