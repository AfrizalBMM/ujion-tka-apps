<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PricingPlanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'string', 'max:40'],
            'original_price' => ['nullable', 'string', 'max:40'],
            'period' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        PricingPlan::create([
            'name' => $validated['name'],
            'subtitle' => $validated['subtitle'] ?? null,
            'price' => $validated['price'],
            'original_price' => $validated['original_price'] ?? null,
            'promo_active' => true,
            'period' => $validated['period'] ?? '/bulan',
            'is_active' => true,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Paket harga ditambahkan',
            'message' => 'Paket baru sudah tersedia dan bisa diatur status aktif atau promonya.',
        ]);
    }

    public function update(Request $request, PricingPlan $pricingPlan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'string', 'max:40'],
            'original_price' => ['nullable', 'string', 'max:40'],
            'period' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $pricingPlan->update([
            'name' => $validated['name'],
            'subtitle' => $validated['subtitle'] ?? null,
            'price' => $validated['price'],
            'original_price' => $validated['original_price'] ?? null,
            'period' => $validated['period'] ?? $pricingPlan->period,
            'sort_order' => (int) ($validated['sort_order'] ?? $pricingPlan->sort_order),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Paket harga diperbarui',
            'message' => 'Informasi harga dan detail paket berhasil disimpan.',
        ]);
    }

    public function toggleActive(PricingPlan $pricingPlan): RedirectResponse
    {
        $pricingPlan->update([
            'is_active' => ! $pricingPlan->is_active,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Status paket harga diperbarui',
            'message' => $pricingPlan->is_active
                ? 'Paket ini sekarang aktif dan dapat dipakai sebagai harga berjalan.'
                : 'Paket ini sekarang nonaktif dan tidak dipakai sebagai harga berjalan.',
        ]);
    }

    public function togglePromo(PricingPlan $pricingPlan): RedirectResponse
    {
        $pricingPlan->update([
            'promo_active' => ! $pricingPlan->promo_active,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Status promo diperbarui',
            'message' => $pricingPlan->promo_active
                ? 'Label promo untuk paket ini sekarang aktif.'
                : 'Label promo untuk paket ini sekarang dimatikan.',
        ]);
    }

    public function destroy(PricingPlan $pricingPlan): RedirectResponse
    {
        $pricingPlan->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Paket harga dihapus',
            'message' => 'Paket ini tidak lagi tersedia untuk alur pendaftaran baru.',
        ]);
    }
}
