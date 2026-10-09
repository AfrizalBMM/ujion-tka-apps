<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Coupon::query()->latest();

        if ($search = $request->get('search')) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $coupons = $query->paginate(20)->withQueryString();

        return Inertia::render('Superadmin/Coupons/Index', [
            'coupons' => $coupons,
            'filters' => $request->only(['search', 'active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,nominal,free'],
            'value' => ['required_if:type,percentage,nominal', 'numeric', 'min:0'],
            'min_transaction' => ['nullable', 'numeric', 'min:0'],
            'max_usage_total' => ['nullable', 'integer', 'min:0'],
            'max_usage_per_user' => ['nullable', 'integer', 'min:0'],
            'jenjang_target' => ['nullable', 'string', 'in:SD,SMP,SMA'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['value'] = $validated['type'] === 'free' ? 0 : ($validated['value'] ?? 0);
        $validated['is_active'] = $validated['is_active'] ?? true;

        Coupon::create($validated);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Kupon dibuat',
            'message' => "Kupon {$validated['code']} berhasil dibuat.",
        ]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code,'.$coupon->id],
            'type' => ['required', 'in:percentage,nominal,free'],
            'value' => ['required_if:type,percentage,nominal', 'numeric', 'min:0'],
            'min_transaction' => ['nullable', 'numeric', 'min:0'],
            'max_usage_total' => ['nullable', 'integer', 'min:0'],
            'max_usage_per_user' => ['nullable', 'integer', 'min:0'],
            'jenjang_target' => ['nullable', 'string', 'in:SD,SMP,SMA'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['value'] = $validated['type'] === 'free' ? 0 : ($validated['value'] ?? 0);

        $coupon->update($validated);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Kupon diperbarui',
            'message' => "Kupon {$coupon->code} berhasil diperbarui.",
        ]);
    }

    public function toggleActive(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Status kupon diperbarui',
            'message' => $coupon->is_active
                ? "Kupon {$coupon->code} diaktifkan."
                : "Kupon {$coupon->code} dinonaktifkan.",
        ]);
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $coupon->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Kupon dihapus',
            'message' => "Kupon {$code} berhasil dihapus.",
        ]);
    }
}
