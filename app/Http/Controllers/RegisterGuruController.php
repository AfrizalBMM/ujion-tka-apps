<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PricingPlan;
use App\Models\PaymentQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'jenjang' => 'required|in:SD,SMP',
            'tingkat' => 'required|in:4,5,6,7,8,9',
            'satuan_pendidikan' => 'required|string|max:255',
            'no_wa' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->no_wa.'@dummy.email', // Placeholder email
            'password' => Hash::make('password'), // Default password, should be updated
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_PENDING,
        ]);

        // Ambil harga aktif dari PricingPlan
        $plan = PricingPlan::where('is_active', true)->orderBy('sort_order')->first();
        // Ambil QR aktif dari PaymentQr
        $qr = PaymentQr::where('is_active', true)->orderBy('sort_order')->first();

        return view('pending-aktivasi', [
            'harga' => $plan?->price,
            'qr_url' => $qr ? asset('storage/' . $qr->image_path) : null,
        ]);
    }
}
