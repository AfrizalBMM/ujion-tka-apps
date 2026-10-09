<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\TrialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrialSettingsController extends Controller
{
    public function __construct(
        private readonly TrialService $trialService
    ) {}

    public function index(): Response
    {
        $currentDays = (int) AppSetting::getValue('trial_default_days', '7');
        $options = $this->trialService->getDurationOptions();

        return Inertia::render('Superadmin/TrialSettings/Index', [
            'currentDays' => $currentDays,
            'options' => $options,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validValues = array_column($this->trialService->getDurationOptions(), 'value');

        $validated = $request->validate([
            'trial_default_days' => ['required', 'integer', 'in:'.implode(',', $validValues)],
        ]);

        AppSetting::putValue('trial_default_days', (string) $validated['trial_default_days']);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pengaturan trial diperbarui',
            'message' => 'Durasi trial default: '.$validated['trial_default_days'].' hari.',
        ]);
    }
}
