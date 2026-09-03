<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\NameMatcher;
use App\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ->withErrors([
                    'email' => 'Email ini sudah dipakai akun lain.',
                    'no_wa' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
                ])
                ->withInput();
        }

        $existingTeacher = $existingByEmail ?? $existingByWa;

        if ($existingTeacher instanceof User) {
            if ($existingTeacher->role === User::ROLE_GURU && $existingTeacher->account_status === User::STATUS_PENDING) {
                $selectedTarifJenjang = $this->resolvePlanForJenjang($validated['jenjang']);
                $this->storePendingRegistrationSession($request, $existingTeacher, $selectedTarifJenjang);

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
                $selectedTarifJenjang = $this->resolvePlanForJenjang($validated['jenjang']);
                $this->storePendingRegistrationSession($request, $existingTeacher, $selectedTarifJenjang);

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

        $selectedTarifJenjang = $this->resolvePlanForJenjang($validated['jenjang']);
        $this->storePendingRegistrationSession($request, $user, $selectedTarifJenjang);

        return redirect()->route('register.guru.pending');
    }

    public function showPending(Request $request): RedirectResponse|View
    {
        $pendingRegistration = $request->session()->get('pending_registration');

        if (! is_array($pendingRegistration) || empty($pendingRegistration['teacher_id'])) {
            return view('pending-aktivasi-resume', [
                'adminWhatsappUrl' => $this->adminWhatsappUrl('Halo Admin Ujion, saya ingin melanjutkan aktivasi akun.'),
            ]);
        }

        $teacher = User::query()->find($pendingRegistration['teacher_id']);
        if (! $teacher) {
            $request->session()->forget('pending_registration');

            return redirect()->route('register.guru.pending')->with('flash', [
                'type' => 'warning',
                'title' => 'Session aktivasi tidak ditemukan',
                'message' => 'Masukkan kembali nama lengkap dan nomor WhatsApp untuk melanjutkan aktivasi.',
            ]);
        }

        $tarifJenjang = $this->resolvePlanForJenjang($teacher->jenjang);
        $latestTransaction = $teacher->transactions()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_SUCCESS])
            ->latest()
            ->first();

        return view('pending-aktivasi', [
            'teacher' => $teacher,
            'harga' => $tarifJenjang?->price,
            'latestTransaction' => $latestTransaction,
            'selectedTarifJenjang' => $tarifJenjang,
            'midtransEnabled' => app(MidtransService::class)->isEnabled(),
            'adminWhatsappUrl' => $this->adminWhatsappUrl('Halo Admin Ujion, saya ingin menyelesaikan pembayaran aktivasi akun guru.'),
        ]);
    }

    public function resumePending(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:20'],
        ]);

        $normalizedWa = $this->normalizePhoneNumber($validated['no_wa']);
        $normalizedName = trim($validated['name']);

        $teacher = User::query()
            ->where('role', User::ROLE_GURU)
            ->where('account_status', User::STATUS_PENDING)
            ->whereIn('no_wa', PhoneNumber::variants($validated['no_wa']))
            ->get()
            ->first(fn (User $candidate) => NameMatcher::matches($candidate->name, $normalizedName));

        if (! $teacher) {
            return back()
                ->withErrors([
                    'resume' => 'Data pending tidak ditemukan. Pastikan nomor WhatsApp sama seperti saat pendaftaran. Nama boleh tanpa gelar.',
                ])
                ->withInput();
        }

        $selectedTarifJenjang = $this->resolvePlanForJenjang($teacher->jenjang);
        $this->storePendingRegistrationSession($request, $teacher, $selectedTarifJenjang);

        return redirect()->route('register.guru.pending')->with('flash', [
            'type' => 'info',
            'title' => 'Data pendaftaran ditemukan',
            'message' => 'Silakan lanjutkan pembayaran aktivasi akun Anda.',
        ]);
    }

    private function storePendingRegistrationSession(Request $request, User $teacher, ?PricingPlan $selectedTarifJenjang = null): void
    {
        $plan = $selectedTarifJenjang ?? $this->resolvePlanForJenjang($teacher->jenjang);

        $request->session()->put('pending_registration', [
            'teacher_id' => $teacher->id,
            'pricing_plan_id' => $plan?->id,
            'harga' => $plan?->price,
        ]);
    }

    private function normalizePhoneNumber(?string $phone): string
    {
        $normalized = PhoneNumber::normalizeIndonesian($phone);

        return PhoneNumber::toLocalFormat($normalized);
    }

    private function adminWhatsappUrl(string $message): ?string
    {
        $adminNumber = PhoneNumber::normalizeIndonesian(
            (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))
        );

        if ($adminNumber === '') {
            return null;
        }

        return "https://wa.me/{$adminNumber}?text=".rawurlencode($message);
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
