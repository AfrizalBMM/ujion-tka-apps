<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PaymentQr;
use App\Models\PricingPlan;
use App\Support\GuruNotificationTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RegisterGuruController extends Controller
{
    public function showForm(): View
    {
        // Ambil harga aktif dari PricingPlan
        $plan = PricingPlan::where('is_active', true)->orderBy('sort_order')->first();
        // Ambil QR aktif dari PaymentQr
        $qr = PaymentQr::where('is_active', true)->orderBy('sort_order')->first();
        return view('register-guru', [
            'harga' => $plan?->price,
            'qr_url' => $qr ? asset('storage/' . $qr->image_path) : null,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'jenjang' => 'required|in:SD,SMP',
            'tingkat' => 'required|in:4,5,6,7,8,9',
            'satuan_pendidikan' => 'required|string|max:255',
            'no_wa' => ['required', 'string', 'max:20', Rule::unique('users', 'no_wa')],
        ]);

        $normalizedWa = preg_replace('/\D+/', '', $validated['no_wa']) ?: $validated['no_wa'];
        $generatedPassword = Str::password(24);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($generatedPassword),
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => $validated['jenjang'],
            'tingkat' => $validated['tingkat'],
            'satuan_pendidikan' => $validated['satuan_pendidikan'],
            'no_wa' => $normalizedWa,
        ]);

        $plan = PricingPlan::where('is_active', true)->orderBy('sort_order')->first();
        $qr = PaymentQr::where('is_active', true)->orderBy('sort_order')->first();

        return redirect()->route('register.guru.pending')->with('pending_registration', [
            'teacher_id' => $user->id,
            'harga' => $plan?->price,
            'qr_url' => $qr ? asset('storage/' . $qr->image_path) : null,
        ]);
    }

    public function showPending(Request $request): RedirectResponse|View
    {
        $pendingRegistration = $request->session()->get('pending_registration');

        if (! is_array($pendingRegistration) || empty($pendingRegistration['teacher_id'])) {
            return redirect()->route('register.guru.form');
        }

        $teacher = User::query()->find($pendingRegistration['teacher_id']);
        if (! $teacher) {
            $request->session()->forget('pending_registration');

            return redirect()->route('register.guru.form');
        }

        $plan = PricingPlan::where('is_active', true)->orderBy('sort_order')->first();
        $qr = PaymentQr::where('is_active', true)->orderBy('sort_order')->first();

        return view('pending-aktivasi', [
            'teacher' => $teacher,
            'harga' => $plan?->price,
            'qr_url' => $qr ? asset('storage/' . $qr->image_path) : null,
        ]);
    }

    public function uploadPaymentProof(Request $request): RedirectResponse
    {
        $pendingRegistration = $request->session()->get('pending_registration');

        if (! is_array($pendingRegistration) || empty($pendingRegistration['teacher_id'])) {
            return redirect()->route('register.guru.form');
        }

        $teacher = User::query()->find($pendingRegistration['teacher_id']);
        if (! $teacher) {
            $request->session()->forget('pending_registration');

            return redirect()->route('register.guru.form');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        $teacher->update([
            'payment_status' => User::PAYMENT_SUBMITTED,
            'payment_proof_path' => $path,
            'payment_submitted_at' => now(),
            'payment_verified_at' => null,
            'payment_reviewed_by' => null,
            'payment_rejection_reason' => null,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Bukti pembayaran berhasil dikirim',
            'message' => 'Tim kami akan memverifikasi pembayaran Anda secepatnya. Setelah disetujui, token akses akan dikirim ke WhatsApp Anda.',
            'copy_block' => GuruNotificationTemplates::paymentSubmittedAlert(
                $teacher->name,
                $teacher->satuan_pendidikan ?? '-',
                $teacher->no_wa ?? '-',
            ),
            'copy_block_label' => 'Template notifikasi untuk admin',
        ]);
    }
}
