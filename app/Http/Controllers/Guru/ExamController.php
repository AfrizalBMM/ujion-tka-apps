<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\JawabanSiswa;
use App\Models\MapelPaket;
use App\Models\Soal;
use App\Models\UjianSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller {
    public function index(): View {
        $user = Auth::user();

        $available = Exam::query()
            ->with('paketSoal.jenjang')
            ->where('status', 'terbit')
            ->where('is_active', true)
            ->whereHas('paketSoal.jenjang', fn ($query) => $query->where('kode', $user->jenjang))
            ->orderByDesc('tanggal_terbit')
            ->get();

        $sessions = $this->sessionQueryForUser($user)
            ->with('exam')
            ->latest('updated_at')
            ->get();

        $joined = $sessions->map(fn (UjianSesi $session) => [
            'exam' => $session->exam,
            'status' => $session->status,
            'score' => $session->skor,
        ]);

        $history = $sessions
            ->where('status', 'selesai')
            ->map(fn (UjianSesi $session) => [
                'judul' => $session->exam?->judul ?? 'Ujian',
                'skor' => $session->skor !== null ? number_format((float) $session->skor, 2) : '-',
                'status' => $session->status,
                'exam_id' => $session->exam_id,
            ])
            ->values();

        return view('guru.exams', compact('available','joined','history'));
    }

    public function join(Request $request): RedirectResponse {
        $request->validate(['token' => 'required|string']);
        $user = Auth::user();
        $token = strtoupper(trim($request->token));

        if (blank($user->no_wa)) {
            return redirect()->route('guru.profile')
                ->with('flash', ['type' => 'warning', 'message' => 'Lengkapi nomor WhatsApp di profil sebelum memulai simulasi.']);
        }

        $exam = Exam::query()
            ->with('paketSoal.mapelPakets', 'paketSoal.jenjang')
            ->where('token', $token)
            ->where('status', 'terbit')
            ->where('is_active', true)
            ->firstOrFail();

        if (($exam->paketSoal?->jenjang?->kode ?? null) !== $user->jenjang) {
            abort(403);
        }

        if (! $exam->paketSoal || $exam->paketSoal->mapelPakets->isEmpty()) {
            return back()->with('flash', ['type' => 'warning', 'message' => 'Paket ujian belum siap dipakai untuk simulasi.']);
        }

        $existingSession = $this->sessionQueryForUser($user)
            ->where('exam_id', $exam->id)
            ->latest('id')
            ->first();

        if ($existingSession?->status === 'selesai') {
            return redirect()->route('guru.exams.result', $exam)
                ->with('flash', ['type' => 'info', 'message' => 'Simulasi ini sudah selesai. Menampilkan hasil terbaru Anda.']);
        }

        $session = $existingSession ?? UjianSesi::create([
            'exam_id' => $exam->id,
            'paket_soal_id' => $exam->paket_soal_id,
            'nama' => $user->name,
            'nomor_wa' => $user->no_wa,
            'session_token' => Str::random(60),
            'status' => 'menunggu',
            'timer_state' => $this->buildTimerState($exam),
        ]);

        session([
            'siswa_token' => $exam->token,
            'participant_token' => $session->session_token,
        ]);

        return redirect()->route('siswa.petunjuk');
    }

    public function result(Exam $exam): View|RedirectResponse {
        $user = Auth::user();
        $session = $this->sessionQueryForUser($user)
            ->where('exam_id', $exam->id)
            ->where('status', 'selesai')
            ->latest('waktu_selesai')
            ->first();

        if (! $session) {
            return redirect()->route('guru.exams')
                ->with('flash', ['type' => 'warning', 'message' => 'Hasil simulasi belum tersedia untuk akun Anda.']);
        }

        $session->load([
            'paketSoal.mapelPakets.soals.pilihanJawabans',
            'paketSoal.mapelPakets.soals.pasanganMenjodohkans',
            'jawabanSiswas',
        ]);

        $answers = $session->jawabanSiswas->keyBy('soal_id');
        $pembahasan = $session->paketSoal->mapelPakets
            ->sortBy('urutan')
            ->flatMap(function (MapelPaket $mapel) use ($answers) {
                return $mapel->soals->map(function (Soal $soal) use ($answers, $mapel) {
                    $answer = $answers->get($soal->id);

                    return [
                        'mapel' => $mapel->nama_label,
                        'pertanyaan' => $soal->pertanyaan,
                        'jawaban_user' => $this->formatUserAnswer($soal, $answer),
                        'jawaban_benar' => $this->formatCorrectAnswer($soal),
                        'pembahasan' => $soal->indikator ?: 'Belum ada pembahasan tersimpan.',
                    ];
                });
            })
            ->values();

        $result = [
            'skor' => number_format((float) $session->skor, 2),
            'status' => $session->status,
            'waktu_selesai' => $session->waktu_selesai,
        ];

        return view('guru.exam-result', compact('exam','result','pembahasan'));
    }

    private function sessionQueryForUser($user)
    {
        return UjianSesi::query()->where('nomor_wa', $user->no_wa);
    }

    private function buildTimerState(Exam $exam): array
    {
        return $exam->paketSoal->mapelPakets
            ->mapWithKeys(fn (MapelPaket $mapel) => [
                $mapel->id => [
                    'duration_seconds' => $mapel->durasi_menit * 60,
                    'remaining_seconds' => $mapel->durasi_menit * 60,
                    'started_at' => null,
                    'finished_at' => null,
                ],
            ])
            ->all();
    }

    private function formatCorrectAnswer(Soal $soal): string
    {
        if ($soal->isPilihanGanda()) {
            return $soal->pilihanJawabans->firstWhere('is_benar', true)?->kode ?? '-';
        }

        return $soal->pasanganMenjodohkans
            ->map(fn ($pair) => "{$pair->teks_kiri} -> {$pair->teks_kanan}")
            ->implode('; ');
    }

    private function formatUserAnswer(Soal $soal, ?JawabanSiswa $answer): string
    {
        if (! $answer) {
            return '-';
        }

        if ($soal->isPilihanGanda()) {
            return $answer->jawaban_pg ?: '-';
        }

        $mapped = collect($answer->jawaban_menjodohkan ?? [])
            ->map(function ($item) use ($soal) {
                $pair = $soal->pasanganMenjodohkans->firstWhere('id', $item['pair_id'] ?? null);
                $match = $soal->pasanganMenjodohkans->firstWhere('id', $item['match_id'] ?? null);

                return $pair && $match
                    ? "{$pair->teks_kiri} -> {$match->teks_kanan}"
                    : null;
            })
            ->filter()
            ->implode('; ');

        return $mapped !== '' ? $mapped : '-';
    }
}
