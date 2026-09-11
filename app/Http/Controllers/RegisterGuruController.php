<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterGuruController extends Controller
{
    public function checkEmail(Request $request): JsonResponse
    {
        $email = $request->query('email');
        if (! $email) {
            return response()->json(['exists' => false]);
        }

        $exists = User::where('email', $email)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Email ini sudah terdaftar. Silakan Login atau gunakan email lain.' : 'Email bisa digunakan.',
        ]);
    }

    public function checkWa(Request $request): JsonResponse
    {
        $no_wa = $request->query('no_wa');
        if (! $no_wa) {
            return response()->json(['exists' => false]);
        }

        $normalizedWa = $this->normalizePhoneNumber($no_wa);
        $exists = User::whereIn('no_wa', PhoneNumber::variants($no_wa))->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Nomor WhatsApp ini sudah terdaftar. Silakan Login atau gunakan nomor lain.' : 'Nomor WhatsApp bisa digunakan.',
        ]);
    }

    public function showForm(): View
    {
        $selectedJenjang = old('jenjang', request()->query('jenjang'));
        $selectedTarifJenjang = $this->resolvePlanForJenjang($selectedJenjang);

        return view('register-guru', [
            'harga' => $selectedTarifJenjang?->price,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255'],
            'jenjang' => 'required|in:'.implode(',', config('ujion.jenjangs')),
            'satuan_pendidikan' => 'required|string|max:255',
            'no_wa' => ['required', 'string', 'max:20'],
        ]);

        $validated['email'] = mb_strtolower(trim((string) $validated['email']));
        $normalizedWa = $this->normalizePhoneNumber($validated['no_wa']);

        $existingByEmail = User::query()->where('email', $validated['email'])->first();
        $existingByWa = User::query()->whereIn('no_wa', PhoneNumber::variants($validated['no_wa']))->first();

        if ($existingByEmail && $existingByWa && $existingByEmail->id !== $existingByWa->id) {
            return back()
                ->withErrors($this->buildDuplicateRegistrationErrors(
                    $validated['email'],
                    $normalizedWa,
                    $existingByEmail,
                    $existingByWa,
                ))
                ->withInput();
        }

        $existingTeacher = $existingByEmail ?? $existingByWa;

        if ($existingTeacher instanceof User) {
            if ($existingTeacher->role === User::ROLE_GURU && $existingTeacher->account_status === User::STATUS_PENDING) {
                $this->loginPendingTeacher($request, $existingTeacher);

                return redirect()->route('guru.dashboard')->with('flash', [
                    'type' => 'info',
                    'title' => 'Pendaftaran sebelumnya masih aktif',
                    'message' => 'Kami menemukan data pendaftaran Anda yang masih pending. Selesaikan pembayaran dari dashboard untuk mengaktifkan akun.',
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

        validator(
            ['email' => $validated['email'], 'no_wa' => $normalizedWa],
            [
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'no_wa' => ['required', 'string', 'max:20', Rule::unique('users', 'no_wa')],
            ],
            [
                'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau login bila akun Anda sudah aktif.',
                'no_wa.unique' => 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau lanjutkan pendaftaran sebelumnya.',
            ]
        )->validate();

        $generatedPassword = Str::password(24);

        try {
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
        } catch (QueryException $e) {
            if (! $this->isDuplicateKeyException($e)) {
                throw $e;
            }

            $existingByEmail = User::query()->where('email', $validated['email'])->first();
            $existingByWa = User::query()->whereIn('no_wa', PhoneNumber::variants($validated['no_wa']))->first();
            $existingTeacher = $existingByEmail ?? $existingByWa;

            if ($existingTeacher instanceof User
                && $existingTeacher->role === User::ROLE_GURU
                && $existingTeacher->account_status === User::STATUS_PENDING) {
                $this->loginPendingTeacher($request, $existingTeacher);

                return redirect()->route('guru.dashboard')->with('flash', [
                    'type' => 'info',
                    'title' => 'Pendaftaran sebelumnya masih aktif',
                    'message' => 'Kami menemukan data pendaftaran Anda yang masih pending. Selesaikan pembayaran dari dashboard untuk mengaktifkan akun.',
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

        $this->loginPendingTeacher($request, $user);

        return redirect()->route('guru.dashboard')->with('flash', [
            'type' => 'success',
            'title' => 'Pendaftaran berhasil',
            'message' => 'Selamat datang! Selesaikan pembayaran aktivasi dari dashboard untuk membuka semua fitur.',
        ]);
    }

    private function loginPendingTeacher(Request $request, User $teacher): void
    {
        Auth::login($teacher);
        $request->session()->regenerate();
    }

    private function normalizePhoneNumber(?string $phone): string
    {
        $normalized = PhoneNumber::normalizeIndonesian($phone);

        return PhoneNumber::toLocalFormat($normalized);
    }

    private function resolvePlanForJenjang(?string $jenjang): ?PricingPlan
    {
        return PricingPlan::resolveForJenjang($jenjang);
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

        if ($existingByWa && in_array($existingByWa->no_wa, PhoneNumber::variants($normalizedWa), true)) {
            $errors['no_wa'] = 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain atau lanjutkan pendaftaran sebelumnya.';
        }

        return $errors;
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return in_array($sqlState, ['23000', '23505'], true)
            || $driverCode === '1062';
    }
}
