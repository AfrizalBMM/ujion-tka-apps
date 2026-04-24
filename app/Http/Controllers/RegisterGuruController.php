<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\PricingPlan;
use App\Models\AppSetting;
use App\Services\QrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RegisterGuruController extends Controller
{
    public function showForm(): View
    {
        $selectedJenjang = old('jenjang', request()->query('jenjang'));
        $selectedTarifJenjang = $this->resolvePlanForJenjang($selectedJenjang);
        $tarifDefault = PricingPlan::where('is_active', true)->first();

        return view('register-guru', [
            'harga' => $selectedTarifJenjang?->price ?? $tarifDefault?->price,
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

        $selectedTarifJenjang = $this->resolvePlanForJenjang($validated['jenjang']);
        $this->storePendingRegistrationSession($request, $user, $selectedTarifJenjang);

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

        $tarifJenjang = $this->resolvePlanForJenjang($teacher->jenjang)
            ?? PricingPlan::where('is_active', true)->first();
        $latestTransaction = $teacher->transactions()->latest()->first();

        return view('pending-aktivasi', [
            'teacher' => $teacher,
            'harga' => $tarifJenjang?->price,
            'latestTransaction' => $latestTransaction,
            'selectedTarifJenjang' => $tarifJenjang,
        ]);
    }

    public function createPayment(Request $request): RedirectResponse
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

        $plan = $this->resolvePlanForJenjang($teacher->jenjang)
            ?? PricingPlan::query()->where('is_active', true)->first();

        if (! $plan) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Tarif jenjang belum tersedia',
                'message' => 'Admin belum mengaktifkan tarif jenjang. Silakan hubungi admin untuk melanjutkan pembayaran.',
            ]);
        }

        $planAmount = (float) $this->sanitizeAmount($plan->price);

        $transaction = $teacher->transactions()
            ->where('status', Transaction::STATUS_PENDING)
            ->where('amount', $planAmount)
            ->latest()
            ->first();

        if (! $transaction) {
            $transaction = $teacher->transactions()->create([
                'pricing_plan_id' => $plan->id,
                'reference_code' => $this->generateReferenceCode(),
                'plan_name' => $plan->name,
                'amount' => $this->sanitizeAmount($plan->price),
                'status' => Transaction::STATUS_PENDING,
            ]);
        }

        return redirect()->route('payments.show', $transaction->reference_code);
    }

    public function paymentData(Request $request, QrisService $qrisService): JsonResponse
    {
        $pendingRegistration = $request->session()->get('pending_registration');

        if (! is_array($pendingRegistration) || empty($pendingRegistration['teacher_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Session pendaftaran tidak ditemukan. Silakan ulangi pendaftaran.',
            ], 419);
        }

        $teacher = User::query()->find($pendingRegistration['teacher_id']);
        if (! $teacher) {
            $request->session()->forget('pending_registration');

            return response()->json([
                'ok' => false,
                'message' => 'Data pendaftar tidak ditemukan. Silakan ulangi pendaftaran.',
            ], 404);
        }

        $plan = $this->resolvePlanForJenjang($teacher->jenjang)
            ?? PricingPlan::query()->where('is_active', true)->first();

        if (! $plan) {
            return response()->json([
                'ok' => false,
                'message' => 'Tarif jenjang belum tersedia. Hubungi admin untuk melanjutkan pembayaran.',
            ], 422);
        }

        $planAmount = (float) $this->sanitizeAmount($plan->price);

        $transaction = $teacher->transactions()
            ->where('status', Transaction::STATUS_PENDING)
            ->where('amount', $planAmount)
            ->latest()
            ->first();

        if (! $transaction) {
            $transaction = $teacher->transactions()->create([
                'pricing_plan_id' => $plan->id,
                'reference_code' => $this->generateReferenceCode(),
                'plan_name' => $plan->name,
                'amount' => $this->sanitizeAmount($plan->price),
                'status' => Transaction::STATUS_PENDING,
            ]);
        }

        $amount = (int) round((float) $transaction->amount);
        $payload = $qrisService->generateFixedAmountPayload($amount);
        $qrCodeSvg = QrCode::format('svg')->size(320)->margin(1)->generate($payload);
        $formattedAmount = 'Rp' . number_format($amount, 0, ',', '.');

        $adminNumber = preg_replace('/\D+/', '', (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))) ?? '';
        $waUrl = null;
        if ($adminNumber !== '') {
            $message = rawurlencode(
                "Halo Admin Ujion,\n"
                . "Saya sudah melakukan pembayaran.\n\n"
                . "Data pendaftar:\n"
                . "Nama: {$teacher->name}\n"
                . "Email: {$teacher->email}\n"
                . "No HP/WA: {$teacher->no_wa}\n"
                . "Jenjang: {$teacher->jenjang}\n\n"
                . "Detail:\n"
                . "Paket: {$transaction->plan_name}\n"
                . "Nominal: {$formattedAmount}\n"
                . "Kode Referensi: {$transaction->reference_code}\n"
            );
            $waUrl = "https://wa.me/{$adminNumber}?text={$message}";
        }

        return response()->json([
            'ok' => true,
            'reference_code' => $transaction->reference_code,
            'plan_name' => $transaction->plan_name,
            'amount' => $formattedAmount,
            'qr_svg' => $qrCodeSvg,
            'wa_url' => $waUrl,
            'upload_url' => route('register.guru.payment-proof'),
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
        $transaction = $teacher->transactions()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_FAILED])
            ->latest()
            ->first();

        if (! $transaction) {
            $plan = $this->resolvePlanForJenjang($teacher->jenjang)
                ?? PricingPlan::query()->where('is_active', true)->first();

            if ($plan) {
                $transaction = $teacher->transactions()->create([
                    'pricing_plan_id' => $plan->id,
                    'reference_code' => $this->generateReferenceCode(),
                    'plan_name' => $plan->name,
                    'amount' => $this->sanitizeAmount($plan->price),
                    'status' => Transaction::STATUS_PENDING,
                ]);
            }
        }

        if ($transaction) {
            $transaction->update([
                'status' => Transaction::STATUS_PENDING,
                'payment_proof_path' => $path,
                'payment_submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
            ]);
        }

        $teacher->update([
            'payment_status' => User::PAYMENT_SUBMITTED,
            'payment_proof_path' => $path,
            'payment_submitted_at' => now(),
            'payment_verified_at' => null,
            'payment_reviewed_by' => null,
            'payment_rejection_reason' => null,
        ]);

        $request->session()->forget('pending_registration');

        $adminNumber = preg_replace('/\D+/', '', (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))) ?? '';
        if ($adminNumber === '') {
            return redirect()->route('login')->with('flash', [
                'type' => 'success',
                'title' => 'Bukti pembayaran berhasil dikirim',
                'message' => 'Bukti pembayaran Anda sudah kami terima. Silakan login kembali setelah admin mengirim token akses.',
                'description' => 'Nomor WhatsApp admin belum dikonfigurasi. Admin bisa mengisinya di menu Superadmin > Keuangan & QR.',
            ]);
        }

        $proofUrl = Storage::url($path);
        $referenceCode = $transaction?->reference_code ?? '-';
        $planName = $transaction?->plan_name ?? ($this->resolvePlanForJenjang($teacher->jenjang)?->name ?? 'Aktivasi Akun Guru');
        $amount = $transaction ? (int) round((float) $transaction->amount) : 0;
        $formattedAmount = 'Rp' . number_format($amount, 0, ',', '.');

        $message = rawurlencode(
            "Halo Admin Ujion,\n"
            . "Saya sudah melakukan pembayaran dan sudah upload bukti di sistem.\n\n"
            . "Data pendaftar:\n"
            . "Nama: {$teacher->name}\n"
            . "Email: {$teacher->email}\n"
            . "No HP/WA: {$teacher->no_wa}\n"
            . "Jenjang: {$teacher->jenjang}\n\n"
            . "Detail:\n"
            . "Paket: {$planName}\n"
            . "Nominal: {$formattedAmount}\n"
            . "Kode Referensi: {$referenceCode}\n"
            . "Bukti (URL): {$proofUrl}\n"
        );

        return redirect()->away("https://wa.me/{$adminNumber}?text={$message}");
    }

    private function storePendingRegistrationSession(Request $request, User $teacher, ?PricingPlan $selectedTarifJenjang = null): void
    {
        $plan = $selectedTarifJenjang ?? $this->resolvePlanForJenjang($teacher->jenjang) ?? PricingPlan::where('is_active', true)->first();

        $request->session()->put('pending_registration', [
            'teacher_id' => $teacher->id,
            'pricing_plan_id' => $plan?->id,
            'harga' => $plan?->price,
        ]);
    }

    private function sanitizeAmount(string|int|float|null $amount): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $amount) ?? '0';

        return $normalized !== '' ? $normalized : '0';
    }

    private function generateReferenceCode(): string
    {
        do {
            $candidate = 'UJN-' . now()->format('ymd') . '-' . strtoupper(Str::random(8));
        } while (Transaction::query()->where('reference_code', $candidate)->exists());

        return $candidate;
    }

    private function resolvePlanForJenjang(?string $jenjang): ?PricingPlan
    {
        $query = PricingPlan::query()->where('is_active', true);

        if ($jenjang && Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            $plan = (clone $query)
                ->where('jenjang', $jenjang)
                ->first();

            if ($plan) {
                return $plan;
            }
        }

        return $query->first();
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
