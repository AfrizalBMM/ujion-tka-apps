<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppBlast;
use App\Models\PaketSoal;
use App\Models\User;
use App\Models\WhatsAppLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppGatewayController extends Controller
{
    public function connection(): Response
    {
        $waGatewayUrl = rtrim((string) config('services.wa_gateway.url'), '/');
        $senderId = (string) config('services.wa_gateway.sender_id');
        $webhookUrl = url('/api/wa-webhook');

        return Inertia::render('Superadmin/WaKoneksi', compact('waGatewayUrl', 'senderId', 'webhookUrl'));
    }

    public function blastForm(Request $request): Response
    {
        $jenjangOptions = User::query()
            ->where('role', User::ROLE_GURU)
            ->whereNotNull('jenjang')
            ->where('jenjang', '!=', '')
            ->distinct()
            ->orderBy('jenjang')
            ->pluck('jenjang')
            ->values()
            ->all();

        $schoolOptions = User::query()
            ->where('role', User::ROLE_GURU)
            ->whereNotNull('satuan_pendidikan')
            ->where('satuan_pendidikan', '!=', '')
            ->distinct()
            ->orderBy('satuan_pendidikan')
            ->limit(50)
            ->pluck('satuan_pendidikan')
            ->values()
            ->all();

        $paketSoalOptions = PaketSoal::query()
            ->with('jenjang')
            ->latest()
            ->get();

        if (empty($jenjangOptions)) {
            $jenjangOptions = ['SD', 'SMP', 'SMA'];
        }

        // Statistik dihitung per pesan (log terbaru per phone+message) —
        // percobaan retry antara tidak dihitung ganda.
        $latestIds = WhatsAppLog::query()
            ->selectRaw('MAX(id) as max_id')
            ->groupBy('phone', 'message')
            ->pluck('max_id');

        $blastStats = WhatsAppLog::query()
            ->whereIn('id', $latestIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $blastLogs = WhatsAppLog::query()
            ->latest()
            ->limit(10)
            ->get();

        $blastLogs->each(function (WhatsAppLog $log) {
            $log->created_at_formatted = $log->created_at?->format('Y-m-d H:i');
            $log->message_limited = Str::limit($log->message, 80);
        });

        return Inertia::render('Superadmin/WaBlast', [
            'jenjangOptions' => $jenjangOptions,
            'schoolOptions' => $schoolOptions,
            'paketSoalOptions' => $paketSoalOptions,
            'blastStats' => $blastStats,
            'blastLogs' => $blastLogs,
        ]);
    }

    public function sendBlast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'target' => ['required', 'in:guru_all_active,guru_jenjang,guru_school,siswa_all,siswa_paket'],
            'jenjang' => ['nullable', 'string', 'max:20'],
            'school' => ['nullable', 'string', 'max:200'],
            'paket_soal_id' => ['nullable', 'integer', 'exists:paket_soals,id'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'confirm' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        abort_unless($user, 401);

        // Rate limiting: max 5 blasts per hour per superadmin.
        $rateLimitKey = "wa-blast:{$user->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Batas blast tercapai',
                'message' => "Anda telah mencapai batas 5 blast per jam. Coba lagi dalam {$retryAfter} detik.",
            ]);
        }

        if ($validated['target'] === 'guru_jenjang' && blank($validated['jenjang'] ?? null)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target belum lengkap',
                'message' => 'Silakan pilih jenjang untuk target blast.',
            ]);
        }

        if ($validated['target'] === 'guru_school' && blank($validated['school'] ?? null)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target belum lengkap',
                'message' => 'Silakan isi nama lembaga/bimbel untuk target blast.',
            ]);
        }

        if ($validated['target'] === 'siswa_paket' && blank($validated['paket_soal_id'] ?? null)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target belum lengkap',
                'message' => 'Silakan pilih paket soal untuk target blast siswa.',
            ]);
        }

        // Filter recipients: only ROLE_GURU users are eligible for blast.
        // Siswa-based targets are rejected since recipients must be ROLE_GURU.
        if (str_starts_with($validated['target'], 'siswa_')) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target tidak diizinkan',
                'message' => 'Blast hanya dapat dikirim ke pengguna dengan peran guru.',
            ]);
        }

        $scheduledAt = null;
        if (! blank($validated['scheduled_at'] ?? null)) {
            $scheduledAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['scheduled_at']);

            if ($scheduledAt === false) {
                return back()->with('flash', [
                    'type' => 'warning',
                    'title' => 'Jadwal tidak valid',
                    'message' => 'Silakan pilih tanggal dan waktu pengiriman blast yang valid.',
                ]);
            }

            if ($scheduledAt->isPast()) {
                return back()->with('flash', [
                    'type' => 'warning',
                    'title' => 'Jadwal tidak valid',
                    'message' => 'Jadwal blast harus di masa depan atau kosong untuk mengirim segera.',
                ]);
            }
        }

        $totalQueued = 0;

        $teachersQuery = User::query()
            ->where('role', User::ROLE_GURU)
            ->where('account_status', User::STATUS_ACTIVE)
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '');

        if ($validated['target'] === 'guru_jenjang') {
            $teachersQuery->where('jenjang', $validated['jenjang']);
        }

        if ($validated['target'] === 'guru_school') {
            $school = trim((string) ($validated['school'] ?? ''));
            $teachersQuery->where('satuan_pendidikan', 'like', '%'.$school.'%');
        }

        // Confirmation requirement: if recipients count > 50, require 'confirm' field.
        $recipientCount = $teachersQuery->count();

        if ($recipientCount > 50 && ! filter_var($validated['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Konfirmasi diperlukan',
                'message' => "Blast ini menargetkan {$recipientCount} penerima (>50). Centang kolom konfirmasi untuk melanjutkan.",
            ]);
        }

        $teachersQuery
            ->orderBy('id')
            ->chunkById(200, function ($teachers) use (&$totalQueued, $validated, $scheduledAt) {
                foreach ($teachers as $teacher) {
                    $delaySeconds = random_int(2, 7);
                    $delay = $scheduledAt ? $scheduledAt->copy()->addSeconds($delaySeconds) : now()->addSeconds($delaySeconds);

                    SendWhatsAppBlast::dispatch($teacher->no_wa, $validated['message'])
                        ->onQueue('low')
                        ->delay($delay);

                    $totalQueued++;
                }
            });

        RateLimiter::hit($rateLimitKey, 3600);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Blast dijadwalkan',
            'message' => "Pesan berhasil dimasukkan ke antrean untuk {$totalQueued} penerima.",
            'description' => 'Pastikan queue worker berjalan agar pesan terkirim.',
        ]);
    }
}
