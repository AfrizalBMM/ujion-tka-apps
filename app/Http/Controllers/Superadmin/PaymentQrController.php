<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentQr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentQrController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'image' => ['required', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('image')->store('payment-qrs', 'public');

        PaymentQr::create([
            'label' => $validated['label'],
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'QR berhasil ditambahkan.']);
    }

    public function update(Request $request, PaymentQr $paymentQr): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'label' => $validated['label'],
            'sort_order' => (int) ($validated['sort_order'] ?? $paymentQr->sort_order),
        ];

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('payment-qrs', 'public');
            Storage::disk('public')->delete($paymentQr->image_path);
            $payload['image_path'] = $newPath;
        }

        $paymentQr->update($payload);

        return back()->with('flash', ['type' => 'success', 'message' => 'QR berhasil diupdate.']);
    }

    public function toggle(PaymentQr $paymentQr): RedirectResponse
    {
        $paymentQr->update([
            'is_active' => ! $paymentQr->is_active,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Status QR diperbarui.']);
    }

    public function destroy(PaymentQr $paymentQr): RedirectResponse
    {
        Storage::disk('public')->delete($paymentQr->image_path);
        $paymentQr->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'QR berhasil dihapus.']);
    }
}
