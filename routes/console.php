<?php

use App\Jobs\SendWhatsAppBlast;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\WaMessageTemplateService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Use file cache for scheduler mutex/overlap locks so scheduler can run
// even when the default cache store is database (e.g., local dev without MySQL).
Schedule::useCache('file');

Schedule::call(function (): void {
    if (! Schema::hasTable('audit_logs')) {
        return;
    }

    $deleted = AuditLog::query()
        ->where('created_at', '<', now()->subDays(30))
        ->delete();

    if ($deleted > 0) {
        Log::info('Audit log cleanup: deleted '.$deleted.' entries older than 30 days.');
    }
})
    ->name('audit-log-cleanup')
    ->dailyAt('03:00');

Schedule::call(function (): void {
    $templates = app(WaMessageTemplateService::class);

    $adminNumbers = [];
    $envAdmins = (string) env('WA_ADMIN_NUMBERS', '');
    if ($envAdmins !== '') {
        foreach (explode(',', $envAdmins) as $raw) {
            $raw = trim($raw);
            if ($raw !== '') {
                $adminNumbers[] = $raw;
            }
        }
    }

    $settingAdmin = (string) (AppSetting::getValue('qris_admin_whatsapp', '') ?? '');
    if ($settingAdmin !== '') {
        foreach (explode(',', $settingAdmin) as $raw) {
            $raw = trim($raw);
            if ($raw !== '') {
                $adminNumbers[] = $raw;
            }
        }
    }

    $adminNumbers = array_values(array_unique(array_filter($adminNumbers)));

    $gatewayUrl = rtrim((string) env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
    $healthUrl = $gatewayUrl.'/';

    $isUp = false;
    $errorMessage = null;

    try {
        $response = Http::timeout(5)->get($healthUrl);
        $isUp = $response->successful();
        if (! $isUp) {
            $errorMessage = 'HTTP '.$response->status();
        }
    } catch (Throwable $e) {
        $isUp = false;
        $errorMessage = $e->getMessage();
    }

    if ($isUp) {
        Cache::forget('wa_gateway.down_since');
        Cache::put('wa_gateway.last_ok_at', now()->toISOString(), now()->addDays(2));

        return;
    }

    Cache::add('wa_gateway.down_since', now()->toISOString(), now()->addDays(2));
    Log::warning('WA Gateway healthcheck failed', [
        'url' => $healthUrl,
        'error' => $errorMessage,
    ]);

    // Throttle alert: max once per 30 minutes.
    $lastAlertAt = Cache::get('wa_gateway.last_alert_at');
    if ($lastAlertAt) {
        try {
            $last = Carbon::parse($lastAlertAt);
            if ($last->diffInMinutes(now()) < 30) {
                return;
            }
        } catch (Throwable) {
            // ignore parse errors
        }
    }

    $text = "[Ujion] WA Gateway DOWN\n".
        "URL: {$healthUrl}\n".
        'Time: '.now()->format('Y-m-d H:i:s')."\n".
        ($errorMessage ? "Error: {$errorMessage}" : '');

    $sent = false;

    $botToken = (string) env('TELEGRAM_BOT_TOKEN', '');
    $chatId = (string) env('TELEGRAM_CHAT_ID', '');
    if ($botToken !== '' && $chatId !== '') {
        try {
            Http::timeout(10)
                ->asForm()
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);
            $sent = true;
        } catch (Throwable $e) {
            Log::warning('Failed sending Telegram alert for WA Gateway', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    if (! empty($adminNumbers)) {
        $waText = $templates->render('alert_gateway_down', [
            'url' => $healthUrl,
            'time' => now()->format('Y-m-d H:i:s'),
            'error' => (string) ($errorMessage ?? ''),
        ], $text);

        foreach ($adminNumbers as $number) {
            SendWhatsAppBlast::dispatch($number, $waText)->onQueue('high');
            $sent = true;
        }
    }

    if ($sent) {
        Cache::put('wa_gateway.last_alert_at', now()->toISOString(), now()->addDays(2));
    }
})
    ->name('wa-gateway-healthcheck')
    ->withoutOverlapping(5)
    ->everyTenMinutes();

Schedule::call(function (): void {
    if (! Schema::hasTable('jobs')) {
        return;
    }

    $jobCount = (int) DB::table('jobs')->count();
    if ($jobCount < 100) {
        return;
    }

    $minAvailableAt = DB::table('jobs')->min('available_at');
    $oldest = null;
    try {
        if ($minAvailableAt) {
            $oldest = Carbon::createFromTimestamp((int) $minAvailableAt);
        }
    } catch (Throwable) {
        $oldest = null;
    }

    if ($oldest && $oldest->diffInMinutes(now()) < 10) {
        return;
    }

    // Throttle alert: max once per 30 minutes.
    $lastAlertAt = Cache::get('wa_queue.last_alert_at');
    if ($lastAlertAt) {
        try {
            $last = Carbon::parse($lastAlertAt);
            if ($last->diffInMinutes(now()) < 30) {
                return;
            }
        } catch (Throwable) {
            // ignore parse errors
        }
    }

    $templates = app(WaMessageTemplateService::class);

    $adminNumbers = [];
    $envAdmins = (string) env('WA_ADMIN_NUMBERS', '');
    if ($envAdmins !== '') {
        foreach (explode(',', $envAdmins) as $raw) {
            $raw = trim($raw);
            if ($raw !== '') {
                $adminNumbers[] = $raw;
            }
        }
    }

    $settingAdmin = (string) (AppSetting::getValue('qris_admin_whatsapp', '') ?? '');
    if ($settingAdmin !== '') {
        foreach (explode(',', $settingAdmin) as $raw) {
            $raw = trim($raw);
            if ($raw !== '') {
                $adminNumbers[] = $raw;
            }
        }
    }

    $adminNumbers = array_values(array_unique(array_filter($adminNumbers)));
    if (empty($adminNumbers)) {
        return;
    }

    $oldestText = $oldest ? $oldest->format('Y-m-d H:i:s') : '-';
    $waText = $templates->render('alert_queue_backlog', [
        'jobs' => $jobCount,
        'oldest' => $oldestText,
        'time' => now()->format('Y-m-d H:i:s'),
    ]);

    foreach ($adminNumbers as $number) {
        SendWhatsAppBlast::dispatch($number, $waText)->onQueue('high');
    }

    Cache::put('wa_queue.last_alert_at', now()->toISOString(), now()->addDays(2));
})
    ->name('wa-queue-backlog-alert')
    ->withoutOverlapping(5)
    ->everyTenMinutes();

Schedule::call(function (): void {
    $templates = app(WaMessageTemplateService::class);

    $teacherQuery = User::query()
        ->where('role', User::ROLE_GURU)
        ->whereNotNull('no_wa')
        ->where('no_wa', '!=', '');

    // Follow-up: pending pembayaran > 24 jam.
    $pending24h = (clone $teacherQuery)
        ->where('payment_status', User::PAYMENT_AWAITING)
        ->where('account_status', User::STATUS_PENDING)
        ->where('created_at', '<=', now()->subHours(24))
        ->orderBy('id');

    $pending24h->chunkById(200, function ($teachers) use ($templates) {
        foreach ($teachers as $teacher) {
            $key = 'wa:followup:pending24h:'.$teacher->id.':'.now()->format('Y-m-d');
            if (! Cache::add($key, '1', now()->addDays(2))) {
                continue;
            }

            $message = $templates->render('followup_payment_pending_24h', [
                'name' => $teacher->name,
            ]);

            SendWhatsAppBlast::dispatch($teacher->no_wa, $message)
                ->onQueue('low')
                ->delay(now()->addSeconds(random_int(2, 7)));
        }
    });
})
    ->name('wa-payment-followups')
    ->withoutOverlapping(10)
    ->everyThirtyMinutes();
