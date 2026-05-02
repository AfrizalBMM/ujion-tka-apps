<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Tampilkan form login untuk Guru.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showForgotTokenForm()
    {
        return view('auth.forgot-token');
    }

    public function requestForgotToken(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', 'in:' . implode(',', config('ujion.jenjangs'))],
        ], [], [
            'name' => 'nama lengkap',
            'contact' => 'email atau nomor WhatsApp aktif',
            'jenjang' => 'jenjang',
        ]);

        $contact = trim((string) $validated['contact']);
        if (! $this->isValidForgotTokenContact($contact)) {
            throw ValidationException::withMessages([
                'contact' => 'Isi email valid atau nomor WhatsApp aktif.',
            ]);
        }

        $adminNumber = PhoneNumber::normalizeIndonesian(
            (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))
        );

        if ($adminNumber === '') {
            return redirect()->route('login')->with('flash', [
                'type' => 'warning',
                'title' => 'Nomor admin belum tersedia',
                'message' => 'Permintaan token belum bisa dikirim otomatis karena WhatsApp admin belum dikonfigurasi.',
                'description' => 'Silakan hubungi admin Ujion melalui kanal resmi sekolah atau operator.',
            ]);
        }

        $message = rawurlencode(trim(implode("\n", [
            'Halo Admin Ujion,',
            '',
            'Saya lupa token akses guru.',
            '',
            'Data verifikasi:',
            "Nama lengkap: {$validated['name']}",
            "Email/No. WhatsApp aktif: {$contact}",
            "Jenjang: {$validated['jenjang']}",
            '',
            'Mohon dibantu pengecekan akun dan pengiriman token akses terbaru jika data saya sesuai.',
        ])));

        return redirect()->away("https://wa.me/{$adminNumber}?text={$message}");
    }

    /**
     * Proses otentikasi Guru (No. WhatsApp + Token).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'no_wa' => ['required', 'string', 'max:20'],
            'access_token' => ['required', 'string'],
        ]);

        $normalizedWa = $this->normalizePhoneNumber($credentials['no_wa']);
        $accessToken = strtoupper(trim((string) $credentials['access_token']));

        $user = User::query()
            ->whereIn('no_wa', PhoneNumber::variants($credentials['no_wa']))
            ->where('role', User::ROLE_GURU)
            ->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($accessToken, $user->access_token)) {
            if ($user->account_status !== User::STATUS_ACTIVE) {
                $message = $user->account_status === User::STATUS_PENDING
                    ? 'Akun Anda masih pending. Token akses akan bisa dipakai setelah pembayaran diverifikasi admin.'
                    : 'Akun Anda sedang ditangguhkan. Silakan hubungi admin.';

                throw ValidationException::withMessages([
                    'access_token' => $message,
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('guru.dashboard'));
        }

        throw ValidationException::withMessages([
            'access_token' => 'No. WA atau Token Akses tidak sesuai.',
        ]);
    }

    /**
     * Tampilkan form login untuk Superadmin.
     */
    public function showAdminLoginForm()
    {
        return view('auth.admin-login');
    }

    /**
     * Proses otentikasi Superadmin (Email + Password).
     */
    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->role !== User::ROLE_SUPERADMIN) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Akses ditolak. Anda bukan Superadmin.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('superadmin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Proses logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function isValidForgotTokenContact(string $contact): bool
    {
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        $digits = $this->normalizePhoneNumber($contact);

        return strlen($digits) >= 8 && strlen($digits) <= 20;
    }

    private function normalizePhoneNumber(?string $phone): string
    {
        return PhoneNumber::normalizeIndonesian($phone);
    }
}
