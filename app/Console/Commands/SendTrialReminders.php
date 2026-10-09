<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppBlast;
use App\Services\TrialService;
use App\Services\WaMessageTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTrialReminders extends Command
{
    protected $signature = 'trial:send-reminders';

    protected $description = 'Send WA reminders to guru whose trial expires within 24 hours (H-1).';

    public function handle(TrialService $trialService, WaMessageTemplateService $waTemplates): int
    {
        $users = $trialService->getUsersExpiringSoon(24);

        if ($users->isEmpty()) {
            $this->info('No users with trials expiring soon.');

            return Command::SUCCESS;
        }

        $pricingUrl = route('pricing');
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            if (! $user->no_wa) {
                Log::info('Trial reminder skipped — no WA number', ['user_id' => $user->id]);

                continue;
            }

            $waBody = $waTemplates->render('event_trial_ending_reminder', [
                'name' => $user->name,
                'trial_ends_date' => $user->trial_ends_at->format('d M Y H:i'),
                'pricing_url' => $pricingUrl,
            ]);

            SendWhatsAppBlast::dispatch($user->no_wa, $waBody)->onQueue('default');
            $sent++;
        }

        $this->info("Sent {$sent} trial reminder(s). Skipped {$failed}.");

        return Command::SUCCESS;
    }
}
