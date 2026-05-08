<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int,int>
     */
    public array $backoff = [60, 180, 300];

    public function __construct(
        public readonly string $number,
        public readonly string $message,
    ) {
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $result = $whatsAppService->sendMessage($this->number, $this->message);

        if (!((bool) ($result['status'] ?? false))) {
            Log::warning('WA blast failed', [
                'number' => $this->number,
                'error' => $result['error'] ?? null,
                'result' => $result,
            ]);

            // Let the job be retried.
            throw new \RuntimeException((string) ($result['error'] ?? 'WA blast failed'));
        }
    }
}
