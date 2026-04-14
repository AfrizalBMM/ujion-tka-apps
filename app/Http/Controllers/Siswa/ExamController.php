<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    public function mulai(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'wa' => 'nullable|string|max:20',
        ]);

        $token = session('siswa_token');
        if (!$token) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Sesi ujian telah habis']);
        }

        $exam = Exam::where('token', $token)->where('is_active', true)->first();
        if (!$exam) {
            return redirect()->route('siswa.login')->withErrors(['token' => 'Ujian tidak ditemukan atau tidak aktif']);
        }

        // Create participant
        $participant = Participant::create([
            'exam_id' => $exam->id,
            'nama' => $request->nama,
            'nomor_wa' => $request->wa,
            'session_token' => Str::random(60),
            'status_ujian' => 'menunggu'
        ]);

        session(['participant_token' => $participant->session_token]);

        return redirect()->route('siswa.petunjuk');
    }

    public function showUjian()
    {
        $participantToken = session('participant_token');
        if (!$participantToken) return redirect()->route('siswa.login');

        $participant = Participant::where('session_token', $participantToken)->firstOrFail();
        $exam = $participant->exam;

        if ($participant->status_ujian === 'menunggu') {
            $participant->update([
                'status_ujian' => 'mengerjakan',
                'waktu_mulai' => now()
            ]);
        } else if ($participant->status_ujian === 'selesai') {
            return redirect()->route('siswa.selesai');
        }

        // Load Questions without correct answer
        $questions = $exam->questions()->select('questions.id', 'pertanyaan', 'opsi', 'tipe', 'image_path')->get()->map(function($q) use ($participant) {
            $answer = ParticipantAnswer::where('participant_id', $participant->id)->where('question_id', $q->id)->first();
            $q->peserta_jawaban = $answer ? $answer->jawaban : null;
            $q->peserta_ragu = $answer ? $answer->ragu_ragu : false;
            if (is_string($q->opsi)) {
                $q->opsi = json_decode($q->opsi, true);
            }
            return $q;
        });

        if ($questions->isEmpty()) {
            return redirect()->route('siswa.petunjuk')->withErrors(['ujian' => 'Soal ujian belum siap.']);
        }

        return view('siswa.ujian', compact('exam', 'participant', 'questions'));
    }

    public function apiSaveAnswer(Request $request)
    {
        $participantToken = session('participant_token');
        if (!$participantToken) return response()->json(['error' => 'Unauthorized'], 401);

        $participant = Participant::where('session_token', $participantToken)->first();
        if (!$participant || $participant->status_ujian === 'selesai') return response()->json(['error' => 'Exam finished'], 403);

        $validated = $request->validate([
            'question_id' => 'required',
            'jawaban' => 'nullable|string',
            'ragu_ragu' => 'boolean'
        ]);

        ParticipantAnswer::updateOrCreate(
            ['participant_id' => $participant->id, 'question_id' => $validated['question_id']],
            ['jawaban' => $validated['jawaban'], 'ragu_ragu' => $validated['ragu_ragu'] ?? false]
        );

        return response()->json(['status' => 'success']);
    }

    public function selesai()
    {
        $participantToken = session('participant_token');
        if ($participantToken) {
            $participant = Participant::where('session_token', $participantToken)->first();
            if ($participant && $participant->status_ujian !== 'selesai') {
                $participant->update([
                    'status_ujian' => 'selesai',
                    'waktu_selesai' => now(),
                    // Evaluasi skor yang sebenarnya bisa dijalankan pakai Job (Async) 
                    'skor' => 0 
                ]);
            }
            session()->forget(['siswa_token', 'participant_token']);
        }
        
        return view('siswa.selesai');
    }
}
