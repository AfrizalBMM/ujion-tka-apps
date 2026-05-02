<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\MaterialPracticePackage;
use App\Models\MaterialPracticePackageAnswer;
use App\Models\MaterialPracticePackageAttempt;
use App\Models\MaterialPracticeSession;
use App\Models\MaterialPracticeToken;
use App\Models\MaterialTelaahAnswer;
use App\Models\MaterialTelaahQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MaterialPracticeController extends Controller
{
    public function mulai(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'wa' => ['nullable', 'string', 'max:20'],
        ]);

        $tokenId = session('siswa_practice_token_id');
        if (! $tokenId) {
            return redirect()->route('siswa.practice.login')
                ->withErrors(['token' => 'Sesi latihan telah habis. Masukkan token kembali.']);
        }

        $token = MaterialPracticeToken::query()->find($tokenId);
        if (! $token || ! $token->is_active) {
            return redirect()->route('siswa.practice.login')
                ->withErrors(['token' => 'Token latihan tidak valid atau tidak aktif.']);
        }

        $existing = null;
        if ($request->wa) {
            $existing = MaterialPracticeSession::query()
                ->where('material_practice_token_id', $token->id)
                ->where('nomor_wa', $request->wa)
                ->latest('id')
                ->first();
        }

        if ($existing) {
            session(['material_practice_session_token' => $existing->session_token]);
            return redirect()->route('siswa.practice.dashboard');
        }

        $session = MaterialPracticeSession::create([
            'material_practice_token_id' => $token->id,
            'nama' => $request->nama,
            'nomor_wa' => $request->wa,
            'session_token' => Str::random(60),
            'status' => 'menunggu',
        ]);

        session(['material_practice_session_token' => $session->session_token]);

        return redirect()->route('siswa.practice.dashboard');
    }

    public function dashboard(): View|RedirectResponse
    {
        $session = $this->getActiveSession();
        if (! $session) {
            return redirect()->route('siswa.practice.login');
        }

        $session->load([
            'token.material',
            'telaahAnswers',
            'packageAttempts',
        ]);

        $token = $session->token;
        $token->load(['packages']);

        $packages = $token->packages()->orderBy('paket_no')->get();
        $attemptsByPackageId = $session->packageAttempts->keyBy('material_practice_package_id');

        $telaahQuestions = MaterialTelaahQuestion::query()
            ->where('material_id', $token->material_id)
            ->with('globalQuestion')
            ->orderBy('urutan')
            ->get();

        $telaahAnswersByQuestionId = $session->telaahAnswers->keyBy('global_question_id');

        return view('siswa.practice.dashboard', compact(
            'session',
            'token',
            'packages',
            'attemptsByPackageId',
            'telaahQuestions',
            'telaahAnswersByQuestionId'
        ));
    }

    public function submitTelaah(Request $request, GlobalQuestion $globalQuestion): RedirectResponse
    {
        $session = $this->getActiveSession();
        if (! $session) {
            return redirect()->route('siswa.practice.login');
        }

        $session->load('token');

        $isTelaah = MaterialTelaahQuestion::query()
            ->where('material_id', $session->token->material_id)
            ->where('global_question_id', $globalQuestion->id)
            ->exists();

        if (! $isTelaah) {
            abort(404);
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'string', 'max:255'],
        ]);

        $jawaban = trim((string) $validated['jawaban']);
        $isCorrect = $globalQuestion->answer_key !== null && $jawaban === $globalQuestion->answer_key;

        MaterialTelaahAnswer::query()->updateOrCreate(
            [
                'material_practice_session_id' => $session->id,
                'global_question_id' => $globalQuestion->id,
            ],
            [
                'jawaban' => $jawaban,
                'is_correct' => $isCorrect,
            ]
        );

        return back()->with('flash', [
            'type' => $isCorrect ? 'success' : 'warning',
            'message' => $isCorrect ? 'Jawaban benar.' : 'Jawaban belum tepat. Silakan coba lagi.',
        ]);
    }

    public function showPaket(Request $request, int $paketNo): View|RedirectResponse
    {
        $session = $this->getActiveSession();
        if (! $session) {
            return redirect()->route('siswa.practice.login');
        }

        $session->load('token');
        $token = $session->token;

        /** @var MaterialPracticePackage $package */
        $package = MaterialPracticePackage::query()
            ->where('material_practice_token_id', $token->id)
            ->where('paket_no', $paketNo)
            ->with('questions')
            ->firstOrFail();

        $attempt = MaterialPracticePackageAttempt::query()
            ->where('material_practice_session_id', $session->id)
            ->where('material_practice_package_id', $package->id)
            ->with('answers')
            ->first();

        if ($attempt && $attempt->status === 'selesai') {
            return redirect()->route('siswa.practice.dashboard')->with('flash', [
                'type' => 'warning',
                'message' => 'Paket ini sudah diselesaikan dan tidak dapat dikerjakan ulang.',
            ]);
        }

        if (! $attempt) {
            $attempt = MaterialPracticePackageAttempt::create([
                'material_practice_session_id' => $session->id,
                'material_practice_package_id' => $package->id,
                'status' => 'mengerjakan',
                'waktu_mulai' => Carbon::now(),
                'total_soal' => $package->questions->count(),
            ]);
        }

        if ($session->status === 'menunggu') {
            $session->update([
                'status' => 'mengerjakan',
                'waktu_mulai' => $session->waktu_mulai ?: Carbon::now(),
            ]);
        }

        $answersByQuestionId = $attempt->answers->keyBy('global_question_id');

        return view('siswa.practice.paket', compact('session', 'token', 'package', 'attempt', 'answersByQuestionId'));
    }

    public function submitPaket(Request $request, int $paketNo): RedirectResponse
    {
        $session = $this->getActiveSession();
        if (! $session) {
            return redirect()->route('siswa.practice.login');
        }

        $session->load('token');
        $token = $session->token;

        /** @var MaterialPracticePackage $package */
        $package = MaterialPracticePackage::query()
            ->where('material_practice_token_id', $token->id)
            ->where('paket_no', $paketNo)
            ->with('questions')
            ->firstOrFail();

        /** @var MaterialPracticePackageAttempt $attempt */
        $attempt = MaterialPracticePackageAttempt::query()
            ->where('material_practice_session_id', $session->id)
            ->where('material_practice_package_id', $package->id)
            ->firstOrFail();

        if ($attempt->status === 'selesai') {
            return redirect()->route('siswa.practice.dashboard')->with('flash', [
                'type' => 'warning',
                'message' => 'Paket ini sudah diselesaikan dan tidak dapat dikerjakan ulang.',
            ]);
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
        ]);

        $answers = is_array($validated['answers'] ?? null) ? $validated['answers'] : [];

        DB::transaction(function () use ($package, $attempt, $answers, $session): void {
            $benar = 0;
            $total = $package->questions->count();

            foreach ($package->questions as $q) {
                $jawaban = isset($answers[$q->id]) ? trim((string) $answers[$q->id]) : null;
                $jawaban = $jawaban === '' ? null : $jawaban;

                $isCorrect = $jawaban !== null && $q->answer_key !== null && $jawaban === $q->answer_key;
                if ($isCorrect) {
                    $benar++;
                }

                MaterialPracticePackageAnswer::query()->updateOrCreate(
                    [
                        'material_practice_package_attempt_id' => $attempt->id,
                        'global_question_id' => $q->id,
                    ],
                    [
                        'jawaban' => $jawaban,
                        'is_correct' => $isCorrect,
                    ]
                );
            }

            $skor = $total > 0 ? round(($benar / $total) * 100, 2) : 0.0;

            $attempt->update([
                'status' => 'selesai',
                'waktu_selesai' => Carbon::now(),
                'total_soal' => $total,
                'benar' => $benar,
                'skor' => $skor,
            ]);

            $allDone = MaterialPracticePackageAttempt::query()
                ->where('material_practice_session_id', $session->id)
                ->where('status', 'selesai')
                ->count() >= 3;

            if ($allDone && $session->status !== 'selesai') {
                $session->update([
                    'status' => 'selesai',
                    'waktu_selesai' => Carbon::now(),
                ]);
            }
        });

        return redirect()->route('siswa.practice.dashboard')->with('flash', [
            'type' => 'success',
            'message' => 'Paket berhasil dikumpulkan. Hasil tersimpan.',
        ]);
    }

    private function getActiveSession(): ?MaterialPracticeSession
    {
        $sessionToken = session('material_practice_session_token');
        if (! $sessionToken) {
            return null;
        }

        return MaterialPracticeSession::query()->where('session_token', $sessionToken)->first();
    }
}
