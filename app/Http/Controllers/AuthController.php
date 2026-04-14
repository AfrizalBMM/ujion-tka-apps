<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    /**
     * Proses otentikasi Guru (Nama + Token).
     */
    public function login(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'access_token' => ['required', 'string'],
        ]);

        $user = User::where('name', $request->name)
                    ->where('access_token', $request->access_token)
                    ->where('role', User::ROLE_GURU)
                    ->first();

        if ($user) {
            if ($user->account_status !== User::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'access_token' => 'Akun Anda belum aktif atau sedang ditangguhkan. Silakan hubungi admin.',
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('guru.dashboard'));
        }

        throw ValidationException::withMessages([
            'access_token' => 'Nama atau Token Akses tidak sesuai.',
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
}
