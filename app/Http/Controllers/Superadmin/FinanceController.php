<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    public function index(): View
    {
        $tarifJenjangs = [];
        if (Schema::hasTable('pricing_plans')) {
            $tarifJenjangsQuery = PricingPlan::query();

            if (Schema::hasColumn('pricing_plans', 'jenjang')) {
                $tarifJenjangsQuery
                    ->orderByRaw("case when jenjang = 'SD' then 1 when jenjang = 'SMP' then 2 when jenjang = 'SMA' then 3 else 4 end")
                    ->orderBy('name');
            } else {
                // Backward-compat: before the per-jenjang tariff migration is applied.
                $tarifJenjangsQuery->orderBy('name');
            }

            $tarifJenjangs = $tarifJenjangsQuery->get();
        }

        $hasJenjangColumn = Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang');
        $hasQrisImageColumn = Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'qris_image_path');
        $adminWhatsapp = AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'));
        $masterPayload = AppSetting::getValue('qris_master_payload', config('services.qris.master_payload'));

        return view('superadmin.finance', compact('tarifJenjangs', 'hasJenjangColumn', 'hasQrisImageColumn', 'adminWhatsapp', 'masterPayload'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_whatsapp' => ['nullable', 'string', 'max:40'],
            'master_payload' => ['nullable', 'string'],
        ]);

        $rawWa = (string) ($validated['admin_whatsapp'] ?? '');
        $digits = PhoneNumber::normalizeIndonesian($rawWa);
        $digits = $digits !== '' ? $digits : null;

        $payload = trim((string) ($validated['master_payload'] ?? ''));
        $payload = $payload !== '' ? $payload : null;

        AppSetting::putValue('qris_admin_whatsapp', $digits);
        AppSetting::putValue('qris_master_payload', $payload);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pengaturan Keuangan & QRIS disimpan',
            'message' => 'Konfigurasi WhatsApp admin dan Payload QRIS berhasil diperbarui.',
        ]);
    }
}
