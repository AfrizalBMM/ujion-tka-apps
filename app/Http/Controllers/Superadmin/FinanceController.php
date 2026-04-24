<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\PricingPlan;
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

        return view('superadmin.finance', compact('tarifJenjangs', 'hasJenjangColumn', 'hasQrisImageColumn', 'adminWhatsapp'));
    }

    public function saveAdminWhatsapp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_whatsapp' => ['nullable', 'string', 'max:40'],
        ]);

        $raw = (string) ($validated['admin_whatsapp'] ?? '');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $digits = $digits !== '' ? $digits : null;

        AppSetting::putValue('qris_admin_whatsapp', $digits);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'WhatsApp admin disimpan',
            'message' => $digits ? 'Nomor WhatsApp admin akan dipakai untuk tombol konfirmasi otomatis.' : 'Nomor WhatsApp admin dikosongkan.',
        ]);
    }
}
