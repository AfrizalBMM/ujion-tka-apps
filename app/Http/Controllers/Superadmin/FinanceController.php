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
        $adminWhatsapp = AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'));

        $midtransSettings = [
            'enabled' => AppSetting::getValue('midtrans_enabled') === '1',
            'environment' => AppSetting::getValue('midtrans_environment', 'sandbox'),
            'server_key' => (string) AppSetting::getValue('midtrans_server_key', ''),
            'client_key' => (string) AppSetting::getValue('midtrans_client_key', ''),
        ];

        return view('superadmin.finance', compact('tarifJenjangs', 'hasJenjangColumn', 'adminWhatsapp', 'midtransSettings'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_whatsapp' => ['nullable', 'string', 'max:40'],
            'midtrans_enabled' => ['nullable', 'boolean'],
            'midtrans_environment' => ['nullable', 'in:sandbox,production'],
            'midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'midtrans_client_key' => ['nullable', 'string', 'max:255'],
        ]);

        $rawWa = (string) ($validated['admin_whatsapp'] ?? '');
        $digits = PhoneNumber::normalizeIndonesian($rawWa);
        $digits = $digits !== '' ? $digits : null;

        AppSetting::putValue('qris_admin_whatsapp', $digits);

        $midtransEnabled = (bool) ($validated['midtrans_enabled'] ?? false);
        $midtransServerKey = trim((string) ($validated['midtrans_server_key'] ?? ''));
        $midtransClientKey = trim((string) ($validated['midtrans_client_key'] ?? ''));

        if ($midtransEnabled && $midtransServerKey === '') {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Server Key Midtrans wajib diisi',
                'message' => 'Aktivasi Midtrans memerlukan Server Key. Isi kredensial dari dashboard Midtrans Anda, lalu centang kembali aktivasinya.',
            ]);
        }

        AppSetting::putValue('midtrans_enabled', $midtransEnabled ? '1' : '0');
        AppSetting::putValue('midtrans_environment', (string) ($validated['midtrans_environment'] ?? 'sandbox'));
        AppSetting::putValue('midtrans_server_key', $midtransServerKey !== '' ? $midtransServerKey : null);
        AppSetting::putValue('midtrans_client_key', $midtransClientKey !== '' ? $midtransClientKey : null);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pengaturan Keuangan disimpan',
            'message' => 'Konfigurasi WhatsApp admin dan Payment Gateway Midtrans berhasil diperbarui.',
        ]);
    }
}
