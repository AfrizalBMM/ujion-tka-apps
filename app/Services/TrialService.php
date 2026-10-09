<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TrialService
{
    /**
     * Default trial duration in days (fallback if AppSetting not set).
     */
    public const DEFAULT_TRIAL_DAYS = 7;

    /**
     * Get the configured default trial duration from AppSetting.
     */
    public function getDefaultDays(): int
    {
        $value = AppSetting::getValue('trial_default_days', (string) self::DEFAULT_TRIAL_DAYS);

        return (int) $value;
    }

    /**
     * Available trial duration options for the settings UI.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function getDurationOptions(): array
    {
        return [
            ['value' => 3, 'label' => '3 Hari'],
            ['value' => 7, 'label' => '7 Hari (Default)'],
            ['value' => 14, 'label' => '14 Hari'],
            ['value' => 30, 'label' => '30 Hari'],
        ];
    }

    /**
     * Start a trial for a user with the given duration (defaults to configured days).
     */
    public function startTrial(User $user, ?int $durationDays = null): User
    {
        $days = $durationDays ?? $this->getDefaultDays();

        $user->update([
            'trial_status' => User::TRIAL_ACTIVE,
            'trial_ends_at' => now()->addDays($days),
        ]);

        return $user->fresh();
    }

    /**
     * Check if a user's trial has expired and update status accordingly.
     */
    public function checkExpiry(User $user): User
    {
        if ($user->trial_status === User::TRIAL_ACTIVE
            && $user->trial_ends_at !== null
            && $user->trial_ends_at->isPast()
        ) {
            $user->update([
                'trial_status' => User::TRIAL_EXPIRED,
            ]);
        }

        return $user->fresh();
    }

    /**
     * Extend a user's trial by extra days.
     */
    public function extendTrial(User $user, int $extraDays): User
    {
        $baseDate = ($user->trial_ends_at && $user->trial_ends_at->isFuture())
            ? $user->trial_ends_at
            : now();

        $user->update([
            'trial_status' => User::TRIAL_ACTIVE,
            'trial_ends_at' => $baseDate->addDays($extraDays),
        ]);

        return $user->fresh();
    }

    /**
     * Check if the user's trial is currently active.
     */
    public function isTrialActive(User $user): bool
    {
        return $user->isTrialActive();
    }

    /**
     * Check if the user's trial has expired.
     */
    public function isTrialExpired(User $user): bool
    {
        return $user->isTrialExpired();
    }

    /**
     * Get users whose trial expires within the given hours (for WA reminder).
     *
     * @return Collection<int, User>
     */
    public function getUsersExpiringSoon(int $withinHours = 24)
    {
        $this->expireStaleTrials();

        return User::query()
            ->where('role', User::ROLE_GURU)
            ->where('trial_status', User::TRIAL_ACTIVE)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->where('trial_ends_at', '<=', now()->addHours($withinHours))
            ->get();
    }

    /**
     * Bulk-expire all active trials that have passed their end date.
     */
    public function expireStaleTrials(): int
    {
        return User::query()
            ->where('trial_status', User::TRIAL_ACTIVE)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update([
                'trial_status' => User::TRIAL_EXPIRED,
            ]);
    }
}
