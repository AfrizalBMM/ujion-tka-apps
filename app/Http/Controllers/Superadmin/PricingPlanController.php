<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use App\Services\QrisService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PricingPlanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'string', 'max:40'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];

        if (Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            $rules['jenjang'] = ['required', 'string', 'in:' . implode(',', config('ujion.jenjangs'))];
        }

        $validated = $request->validate($rules);

        $pricingPlan = PricingPlan::query()->firstOrNew(
            Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang') && isset($validated['jenjang'])
                ? ['jenjang' => $validated['jenjang']]
                : []
        );

        $pricingPlan->fill([
            'name' => $validated['name'],
            'jenjang' => $validated['jenjang'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_active' => true,
        ]);

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('qris-jenjang', 'public');

            if (! blank($pricingPlan->qris_image_path)) {
                Storage::disk('public')->delete($pricingPlan->qris_image_path);
            }

            $pricingPlan->qris_image_path = $newPath;
        }

        $pricingPlan->save();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Tarif jenjang disimpan',
            'message' => 'Nominal untuk jenjang ini sudah siap dipakai di flow pendaftaran guru.',
        ]);
    }

    public function update(Request $request, PricingPlan $pricingPlan): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'string', 'max:40'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];

        if (Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            $rules['jenjang'] = ['required', 'string', 'in:' . implode(',', config('ujion.jenjangs'))];
        }

        $validated = $request->validate($rules);

        $pricingPlan->fill([
            'name' => $validated['name'],
            'jenjang' => $validated['jenjang'] ?? $pricingPlan->jenjang,
            'subtitle' => $validated['subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_active' => true,
        ]);

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('qris-jenjang', 'public');
            if (! blank($pricingPlan->qris_image_path)) {
                Storage::disk('public')->delete($pricingPlan->qris_image_path);
            }
            $pricingPlan->qris_image_path = $newPath;
        }

        $pricingPlan->save();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Tarif jenjang diperbarui',
            'message' => 'Informasi judul, jenjang, dan nominal berhasil disimpan.',
        ]);
    }

    public function toggleActive(PricingPlan $pricingPlan): RedirectResponse
    {
        $pricingPlan->update([
            'is_active' => ! $pricingPlan->is_active,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Status tarif jenjang diperbarui',
            'message' => $pricingPlan->is_active
                ? 'Tarif ini sekarang aktif dan dapat dipakai sebagai nominal berjalan.'
                : 'Tarif ini sekarang nonaktif dan tidak dipakai sebagai nominal berjalan.',
        ]);
    }

    public function destroy(PricingPlan $pricingPlan): RedirectResponse
    {
        if (! blank($pricingPlan->qris_image_path)) {
            Storage::disk('public')->delete($pricingPlan->qris_image_path);
        }

        $pricingPlan->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Tarif jenjang dihapus',
            'message' => 'Tarif ini tidak lagi tersedia untuk alur pendaftaran baru.',
        ]);
    }

    public function printLabel(PricingPlan $pricingPlan, QrisService $qrisService): View
    {
        $amount = $this->sanitizeAmount($pricingPlan->price);
        $qrisPayload = $qrisService->generateFixedAmountPayload($amount);

        $qrisImageUrl = null;
        if (! blank($pricingPlan->qris_image_path)) {
            $qrisImageUrl = route('superadmin.tarif-jenjang.image', $pricingPlan);
        }

        return view('superadmin.pricing-plans.print', [
            'tarifJenjang' => $pricingPlan,
            'formattedPrice' => number_format((int) $amount, 0, ',', '.'),
            'qrCodeSvg' => QrCode::format('svg')->size(250)->margin(1)->generate($qrisPayload),
            'qrisImageUrl' => $qrisImageUrl,
        ]);
    }

    public function image(PricingPlan $pricingPlan): StreamedResponse
    {
        if (blank($pricingPlan->qris_image_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($pricingPlan->qris_image_path);
    }

    private function sanitizeAmount(string|int|float|null $amount): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $amount) ?? '0';

        return $normalized !== '' ? $normalized : '0';
    }
}
