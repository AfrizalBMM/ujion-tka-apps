<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PaymentQr;
use App\Models\PricingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'email' => ['required', 'email', 'max:255'],
            'jenjang' => 'required|in:' . implode(',', config('ujion.jenjangs')),
            'satuan_pendidikan' => 'required|string|max:255',
            'no_wa' => ['required', 'string', 'max:20'],
        ]);

        $normalizedWa = preg_replace('/\D+/', '', $validated['no_wa']) ?: $validated['no_wa'];
        $existingByEmail = User::query()->where('email', $validated['email'])->first();
        $existingByWa = User::query()->where('no_wa', $normalizedWa)->first();

        if ($existingByEmail && $existingByWa && $existingByEmail->id !== $existingByWa->id) {
            return back()
                ->withErrors([
                    'email' => 'Email ini sudah dipakai akun lain.',
                    'no_wa' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
                ])
                ->withInput();
        }

        $existingTeacher = $existingByEmail ?? $existingByWa;

        if ($existingTeacher instanceof User) {
            if ($existingTeacher->role === User::ROLE_GURU && $existingTeacher->account_status === User::STATUS_PENDING) {
                $this->storePendingRegistrationSession($request, $existingTeacher);

                return redirect()->route('register.guru.pending')->with('flash', [
                    'type' => 'info',
                    'title' => 'Pendaftaran sebelumnya masih aktif',
                    'message' => 'Kami menemukan data pendaftaran Anda yang masih pending. Silakan lanjutkan dari halaman aktivasi pembayaran.',
                ]);
            }

            return back()
                ->withErrors($this->buildDuplicateRegistrationErrors(
                    $validated['email'],
                    $normalizedWa,
                    $existingByEmail,
                    $existingByWa,
                ))
                ->withInput();
        }

        $generatedPassword = Str::password(24);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($generatedPassword),
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'payment_status' => User::PAYMENT_AWAITING,
            'jenjang' => $validated['jenjang'],
            'satuan_pendidikan' => $validated['satuan_pendidikan'],
            'no_wa' => $normalizedWa,
        ]);

        $this->storePendingRegistrationSession($request, $user);

        return redirect()->route('register.guru.pending');
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

        $request->session()->forget('pending_registration');

        return redirect()->route('login')->with('flash', [
            'type' => 'success',
            'title' => 'Bukti pembayaran berhasil dikirim',
            'message' => 'Bukti pembayaran Anda sudah kami terima. Silakan login kembali setelah admin mengirim token akses.',
            'description' => 'Selama akun masih pending, halaman guru akan menampilkan informasi bahwa verifikasi masih diproses.',
        ]);
    }

    private function storePendingRegistrationSession(Request $request, User $teacher): void
    {
        $plan = PricingPlan::where('is_active', true)->orderBy('sort_order')->first();
        $qr = PaymentQr::where('is_active', true)->orderBy('sort_order')->first();

        $request->session()->put('pending_registration', [
            'teacher_id' => $teacher->id,
            'harga' => $plan?->price,
            'qr_url' => $qr ? asset('storage/' . $qr->image_path) : null,
        ]);
    }

    private function buildDuplicateRegistrationErrors(
        string $email,
        string $normalizedWa,
        ?User $existingByEmail,
        ?User $existingByWa,
    ): array {
        $errors = [];

        if ($existingByEmail?->email === $email) {
            $errors['email'] = 'Email ini sudah terdaftar. Silakan gunakan email lain atau login bila akun Anda sudah aktif.';
        }

        if ($existingByWa?->no_wa === $normalizedWa) {
            $errors['no_wa'] = 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau lanjutkan pendaftaran sebelumnya.';
        }

        return $errors;
    }
}
