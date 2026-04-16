<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PaymentQr;
use App\Models\PricingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterGuruController extends Controller
{
    public function showForm()
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

    public function register(Request $request)
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

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($generatedPassword),
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
            'jenjang' => $validated['jenjang'],
            'tingkat' => $validated['tingkat'],
            'satuan_pendidikan' => $validated['satuan_pendidikan'],
            'no_wa' => $normalizedWa,
        ]);

        $plan = PricingPlan::where('is_active', true)->orderBy('sort_order')->first();
        $qr = PaymentQr::where('is_active', true)->orderBy('sort_order')->first();

        return redirect()->route('register.guru.pending')->with('pending_registration', [
            'harga' => $plan?->price,
            'qr_url' => $qr ? asset('storage/' . $qr->image_path) : null,
        ]);
    }
}
