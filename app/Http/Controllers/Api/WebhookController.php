<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use App\Services\WaMessageTemplateService;
use App\Support\PhoneNumber;
use App\Support\TokenGenerator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $debug = (bool) config('services.wa_webhook.debug');

        $expectedKey = (string) config('services.wa_webhook.key');
        if ($expectedKey === '') {
            Log::critical('WA webhook rejected: WA_WEBHOOK_KEY is not configured. Set it in .env to enable the webhook.');

            return response()->json(['success' => false, 'msg' => 'Webhook not configured'], 503);
        }

        $provided = (string) $request->header('X-WA-WEBHOOK-KEY', '');
        if (! hash_equals($expectedKey, $provided)) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized'], 401);
        }

        $from = $this->extractFrom($request);
        $message = $this->extractMessage($request);

        $chat = (string) ($request->input('chat') ?? $request->input('remoteJid') ?? '');
        $isGroup = (bool) ($request->input('is_group') ?? false);
        if (! $isGroup && is_string($chat) && str_contains($chat, '@g.us')) {
            $isGroup = true;
        }

        $device = (string) ($request->input('device') ?? '');
        $isLid = (bool) ($request->input('is_lid') ?? false);
        if (! $isLid && is_string($chat) && str_contains($chat, '@lid')) {
            $isLid = true;
        }

        $identityKey = trim($device.'|'.($chat !== '' ? $chat : $from));
        $cache = Cache::store('file');

        $normalizedFrom = PhoneNumber::normalizeIndonesian($from);
        $normalizedMessage = mb_strtoupper(trim($message));

        if ($debug) {
            Log::info('WA webhook incoming', [
                'from_raw' => $from,
                'from_normalized' => $normalizedFrom,
                'from_variants' => PhoneNumber::variants($normalizedFrom),
                'message' => $normalizedMessage,
                'device' => (string) ($request->input('device') ?? ''),
                'chat' => (string) ($request->input('chat') ?? $request->input('remoteJid') ?? ''),
                'is_group' => (bool) ($request->input('is_group') ?? false),
            ]);
        }

        if ($normalizedMessage === '' || $normalizedFrom === '') {
            return response()->json(['success' => true, 'msg' => '']);
        }

        $templates = app(WaMessageTemplateService::class);

        if (in_array($normalizedMessage, ['HALO', 'MENU', 'P'], true)) {
            return response()->json(['success' => true, 'msg' => $templates->render('bot_menu')]);
        }

        if (in_array($normalizedMessage, ['2', 'LUPA TOKEN', 'LUPA TOKEN LOGIN'], true)) {
            if ($isGroup) {
                return response()->json([
                    'success' => true,
                    'msg' => $templates->render('bot_lupa_token_group_only'),
                ]);
            }

            $resolved = $this->resolveTeacherFromSender(
                cache: $cache,
                identityKey: $identityKey,
                isLid: $isLid,
                normalizedFrom: $normalizedFrom
            );
            if ($resolved['error'] ?? null) {
                return response()->json(['success' => true, 'msg' => (string) $resolved['error']]);
            }

            /** @var User|null $teacher */
            $teacher = $resolved['teacher'] ?? null;

            if (! $teacher) {
                if ($debug) {
                    Log::warning('WA LUPA TOKEN not registered', [
                        'from_raw' => $from,
                        'from_normalized' => $normalizedFrom,
                        'from_variants' => PhoneNumber::variants($normalizedFrom),
                    ]);
                }

                return response()->json(['success' => true, 'msg' => $templates->render('bot_not_registered_guru')]);
            }

            if ($teacher->account_status !== User::STATUS_ACTIVE) {
                return response()->json([
                    'success' => true,
                    'msg' => $templates->render('bot_account_not_active'),
                ]);
            }

            // Access token disimpan dalam bentuk hash, jadi untuk "lupa token" kita buat token baru.
            $newToken = TokenGenerator::uniqueTeacherToken();
            $teacher->update(['access_token' => $newToken]);

            return response()->json([
                'success' => true,
                'msg' => $templates->render('bot_token_reset_success', [
                    'name' => $teacher->name,
                    'token' => $newToken,
                ]),
            ]);
        }

        if (in_array($normalizedMessage, ['1', 'CEK HASIL'], true)) {
            // Implementasi detail cek hasil tergantung model skor yang dipakai.
            // Untuk saat ini, berikan arahan aman tanpa membuat klaim nilai.
            return response()->json([
                'success' => true,
                'msg' => 'Untuk melihat hasil terbaru, silakan login ke dashboard Ujion. Jika Anda butuh bantuan, balas dengan LUPA TOKEN atau ketik MENU.',
            ]);
        }

        if (in_array($normalizedMessage, ['3', 'STATUS', 'CEK STATUS', 'CEK STATUS AKUN'], true)) {
            $resolved = $this->resolveTeacherFromSender(
                cache: $cache,
                identityKey: $identityKey,
                isLid: $isLid,
                normalizedFrom: $normalizedFrom
            );
            if ($resolved['error'] ?? null) {
                return response()->json(['success' => true, 'msg' => (string) $resolved['error']]);
            }

            /** @var User|null $teacher */
            $teacher = $resolved['teacher'] ?? null;
            if (! $teacher) {
                return response()->json(['success' => true, 'msg' => $templates->render('bot_not_registered_guru')]);
            }

            $accountLabel = $this->humanAccountStatus((string) $teacher->account_status);
            $paymentLabel = $this->humanPaymentStatus((string) $teacher->payment_status);
            $note = $teacher->account_status === User::STATUS_ACTIVE
                ? 'Akun Anda sudah aktif. Jika lupa token, balas dengan LUPA TOKEN.'
                : 'Akun Anda belum aktif. Silakan tunggu verifikasi pembayaran atau hubungi admin.';

            return response()->json([
                'success' => true,
                'msg' => $templates->render('bot_status', [
                    'name' => $teacher->name,
                    'account_status' => $accountLabel,
                    'payment_status' => $paymentLabel,
                    'status_note' => $note,
                ]),
            ]);
        }

        if (in_array($normalizedMessage, ['4', 'LINK', 'KIRIM ULANG LINK', 'KIRIM ULANG LINK LOGIN', 'KIRIM ULANG LINK LOGIN/DASHBOARD', 'LOGIN', 'DASHBOARD'], true)) {
            $loginUrl = url('/login');
            $dashboardUrl = url('/guru/dashboard');

            $loginBlock = "Login: {$loginUrl}\nDashboard: {$dashboardUrl}";

            return response()->json([
                'success' => true,
                'msg' => $templates->render('bot_login_link', [
                    'login_url' => $loginBlock,
                    'dashboard_url' => $dashboardUrl,
                ]),
            ]);
        }

        if (in_array($normalizedMessage, ['5', 'JADWAL', 'JADWAL UJIAN', 'UJIAN'], true)) {
            $resolved = $this->resolveTeacherFromSender(
                cache: $cache,
                identityKey: $identityKey,
                isLid: $isLid,
                normalizedFrom: $normalizedFrom
            );
            if ($resolved['error'] ?? null) {
                return response()->json(['success' => true, 'msg' => (string) $resolved['error']]);
            }

            /** @var User|null $teacher */
            $teacher = $resolved['teacher'] ?? null;
            if (! $teacher) {
                return response()->json(['success' => true, 'msg' => $templates->render('bot_not_registered_guru')]);
            }

            $exams = Exam::query()
                ->where('user_id', $teacher->id)
                ->where('status', 'terbit')
                ->orderBy('tanggal_terbit')
                ->limit(5)
                ->get(['judul', 'tanggal_terbit']);

            if ($exams->isEmpty()) {
                $lines = 'Belum ada jadwal ujian/tryout yang diterbitkan dari akun Anda.';
            } else {
                $lines = "Jadwal ujian/tryout terbit (maks. 5 terdekat):\n";

                foreach ($exams as $index => $exam) {
                    $when = $exam->tanggal_terbit
                        ? Carbon::parse($exam->tanggal_terbit)->translatedFormat('d M Y H:i')
                        : '-';
                    $number = $index + 1;
                    $lines .= "{$number}. {$exam->judul} — {$when}\n";
                }

                $lines = trim($lines);
            }

            return response()->json([
                'success' => true,
                'msg' => $templates->render('bot_jadwal', [
                    'name' => $teacher->name,
                    'jadwal_lines' => $lines,
                ]),
            ]);
        }

        return response()->json(['success' => true, 'msg' => '']);
    }

    private function resolveTeacherFromSender($cache, string $identityKey, bool $isLid, string $normalizedFrom): array
    {
        if ($isLid) {
            $mappedTeacherId = (int) $cache->get('wa:lidmap:'.$identityKey, 0);
            if ($mappedTeacherId > 0) {
                $teacher = User::query()
                    ->where('id', $mappedTeacherId)
                    ->where('role', User::ROLE_GURU)
                    ->first();

                if ($teacher) {
                    return ['teacher' => $teacher];
                }
            }

            $templates = app(WaMessageTemplateService::class);

            return ['error' => $templates->render('bot_lid_unreadable')];
        }

        $teacher = User::query()
            ->where('role', User::ROLE_GURU)
            ->whereIn('no_wa', PhoneNumber::variants($normalizedFrom))
            ->first();

        if (! $teacher) {
            $needle = PhoneNumber::digitsOnly($normalizedFrom);
            $suffix = $needle !== '' ? substr($needle, -9) : '';
            if ($suffix !== '') {
                $teacher = User::query()
                    ->where('role', User::ROLE_GURU)
                    ->where('no_wa', 'like', '%'.$suffix)
                    ->first();
            }
        }

        if ($teacher) {
            return ['teacher' => $teacher];
        }

        $anyUser = User::query()
            ->whereIn('no_wa', PhoneNumber::variants($normalizedFrom))
            ->first();

        if (! $anyUser) {
            $needle = PhoneNumber::digitsOnly($normalizedFrom);
            $suffix = $needle !== '' ? substr($needle, -9) : '';
            if ($suffix !== '') {
                $anyUser = User::query()
                    ->where('no_wa', 'like', '%'.$suffix)
                    ->first();
            }
        }

        if ($anyUser) {
            $templates = app(WaMessageTemplateService::class);

            return ['error' => $templates->render('bot_not_guru')];
        }

        return ['teacher' => null];
    }

    private function humanAccountStatus(string $value): string
    {
        return match ($value) {
            User::STATUS_ACTIVE => 'Aktif',
            User::STATUS_PENDING => 'Menunggu',
            User::STATUS_SUSPEND => 'Ditangguhkan',
            default => $value,
        };
    }

    private function humanPaymentStatus(string $value): string
    {
        return match ($value) {
            User::PAYMENT_APPROVED => 'Disetujui',
            User::PAYMENT_REJECTED => 'Ditolak (perlu perbaikan)',
            User::PAYMENT_SUBMITTED => 'Menunggu review',
            User::PAYMENT_AWAITING => 'Menunggu pembayaran',
            default => $value,
        };
    }

    private function extractMessage(Request $request): string
    {
        $message = $request->input('message');

        if (is_string($message)) {
            return $message;
        }

        // Be tolerant to nested payloads.
        $nested = $request->input('data.message')
            ?? $request->input('payload.message')
            ?? $request->input('text');

        return is_string($nested) ? $nested : '';
    }

    private function extractFrom(Request $request): string
    {
        $candidates = [
            $request->input('from'),
            $request->input('sender'),
            $request->input('participant'),
            $request->input('number'),
            $request->input('data.from'),
            $request->input('data.sender'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $this->sanitizeFrom($candidate);
            }
        }

        return '';
    }

    private function sanitizeFrom(string $raw): string
    {
        $value = trim($raw);

        // Handle Baileys JIDs like "628xxx:17@s.whatsapp.net" or "628xxx@s.whatsapp.net".
        if (str_contains($value, '@')) {
            $value = explode('@', $value, 2)[0] ?? $value;
        }
        if (str_contains($value, ':')) {
            $value = explode(':', $value, 2)[0] ?? $value;
        }

        return trim($value);
    }
}
