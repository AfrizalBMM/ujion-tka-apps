<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupPaymentProofs extends Command
{
    protected $signature = 'storage:cleanup-payment-proofs
        {--days=90 : Delete unreferenced payment proof files older than this many days}
        {--dry-run : Show what would be deleted without deleting files}';

    protected $description = 'Delete old unreferenced payment proof files from public storage.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoffTimestamp = now()->subDays($days)->getTimestamp();

        $referencedPaths = User::query()
            ->whereNotNull('payment_proof_path')
            ->pluck('payment_proof_path')
            ->merge(
                Transaction::query()
                    ->whereNotNull('payment_proof_path')
                    ->pluck('payment_proof_path')
            )
            ->filter()
            ->unique()
            ->flip();

        $disk = Storage::disk('public');
        $files = $disk->files('payment-proofs');
        $deleted = 0;
        $skipped = 0;

        foreach ($files as $file) {
            if ($referencedPaths->has($file) || $disk->lastModified($file) > $cutoffTimestamp) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("Would delete: {$file}");
            } else {
                $disk->delete($file);
                $this->line("Deleted: {$file}");
            }

            $deleted++;
        }

        $action = $dryRun ? 'matched' : 'deleted';
        $this->info("Payment proof cleanup {$action} {$deleted} file(s). Skipped {$skipped} file(s).");

        return self::SUCCESS;
    }
}
