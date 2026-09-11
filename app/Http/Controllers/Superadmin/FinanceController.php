<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(): Response
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
        $adminWhatsapp = AppSetting::getValue('admin_whatsapp', config('services.admin.whatsapp'));

        $dokuSettings = [
            'enabled' => AppSetting::getValue('doku_enabled') === '1',
            'client_id' => (string) AppSetting::getValue('doku_client_id', ''),
            'secret_key' => (string) AppSetting::getValue('doku_secret_key', ''),
        ];

        $jenjangs = config('ujion.jenjangs');

        return Inertia::render('Superadmin/Finance', compact('tarifJenjangs', 'hasJenjangColumn', 'adminWhatsapp', 'dokuSettings', 'jenjangs'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_whatsapp' => ['nullable', 'string', 'max:40'],
            'doku_enabled' => ['nullable', 'boolean'],
            'doku_client_id' => ['nullable', 'string', 'max:255'],
            'doku_secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        $rawWa = (string) ($validated['admin_whatsapp'] ?? '');
        $digits = PhoneNumber::normalizeIndonesian($rawWa);
        $digits = $digits !== '' ? $digits : null;

        AppSetting::putValue('admin_whatsapp', $digits);

        $dokuEnabled = (bool) ($validated['doku_enabled'] ?? false);
        $dokuClientId = trim((string) ($validated['doku_client_id'] ?? ''));
        $dokuSecretKey = trim((string) ($validated['doku_secret_key'] ?? ''));

        if ($dokuEnabled && ($dokuClientId === '' || $dokuSecretKey === '')) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Kredensial Doku wajib diisi',
                'message' => 'Aktivasi Doku memerlukan Client-Id dan Secret-Key. Isi kredensial dari dashboard Doku Anda, lalu centang kembali aktivasinya.',
            ]);
        }

        AppSetting::putValue('doku_enabled', $dokuEnabled ? '1' : '0');
        AppSetting::putValue('doku_client_id', $dokuClientId !== '' ? $dokuClientId : null);
        AppSetting::putValue('doku_secret_key', $dokuSecretKey !== '' ? $dokuSecretKey : null);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pengaturan Keuangan disimpan',
            'message' => 'Konfigurasi WhatsApp admin dan Payment Gateway Doku berhasil diperbarui.',
        ]);
    }
}
