<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppBlast;
use App\Models\AppSetting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentProofStorage;
use App\Services\QrisService;
use App\Support\GuruNotificationTemplates;
use App\Support\NameMatcher;
use App\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
            'message' => 'Silakan lanjutkan pembayaran dan unggah bukti pembayaran.',
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

        $plan = $this->resolvePlanForJenjang($teacher->jenjang);

        if (! $plan) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Tarif jenjang belum tersedia',
                'message' => 'Admin belum mengaktifkan tarif jenjang. Silakan hubungi admin untuk melanjutkan pembayaran.',
            ]);
        }

        $planAmount = (float) $this->sanitizeAmount($plan->price);
        $transaction = $this->resolveOrCreatePendingTransaction($teacher, $plan);

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

        $plan = $this->resolvePlanForJenjang($teacher->jenjang);

        if (! $plan) {
            return response()->json([
                'ok' => false,
                'message' => 'Tarif jenjang belum tersedia. Hubungi admin untuk melanjutkan pembayaran.',
            ], 422);
        }

        $transaction = $this->resolveOrCreatePendingTransaction($teacher, $plan);

        $amount = (int) round((float) $transaction->amount);

        try {
            $payload = $qrisService->generateFixedAmountPayload($amount);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Konfigurasi pembayaran belum selesai. Silakan hubungi admin untuk menyelesaikan pembayaran.',
            ], 503);
        }

        $qrCodeSvg = (string) QrCode::format('svg')->size(320)->margin(1)->generate($payload);
        $formattedAmount = 'Rp'.number_format($amount, 0, ',', '.');

        $adminNumber = PhoneNumber::normalizeIndonesian(
            (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))
        );
        $waUrl = null;
        if ($adminNumber !== '') {
            $message = rawurlencode(
                "Halo Admin Ujion,\n"
                ."Saya sudah melakukan pembayaran.\n\n"
                ."Data pendaftar:\n"
                ."Nama: {$teacher->name}\n"
                ."Email: {$teacher->email}\n"
                ."No HP/WA: {$teacher->no_wa}\n"
                ."Jenjang: {$teacher->jenjang}\n\n"
                ."Detail:\n"
                ."Paket: {$transaction->plan_name}\n"
                ."Nominal: {$formattedAmount}\n"
                ."Kode Referensi: {$transaction->reference_code}\n"
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

    public function uploadPaymentProof(Request $request, PaymentProofStorage $paymentProofStorage): RedirectResponse
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

        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $transaction = $teacher->transactions()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_FAILED])
            ->latest()
            ->first();

        if (! $transaction) {
            $plan = $this->resolvePlanForJenjang($teacher->jenjang);

            if (! $plan) {
                return back()->with('flash', [
                    'type' => 'warning',
                    'title' => 'Tarif jenjang belum tersedia',
                    'message' => 'Admin belum mengaktifkan tarif jenjang Anda. Silakan hubungi admin sebelum mengirim bukti pembayaran.',
                ]);
            }

            $transaction = $teacher->transactions()->create([
                'pricing_plan_id' => $plan->id,
                'reference_code' => $this->generateReferenceCode(),
                'plan_name' => $plan->name,
                'amount' => $this->sanitizeAmount($plan->price),
                'status' => Transaction::STATUS_PENDING,
            ]);
        }

        $oldProofPaths = collect([
            $teacher->payment_proof_path,
            $transaction?->payment_proof_path,
        ])->filter()->unique()->values();

        try {
            $path = $paymentProofStorage->store($request->file('payment_proof'));
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['payment_proof' => $e->getMessage()])
                ->withInput();
        }

        DB::transaction(function () use ($transaction, $teacher, $path): void {
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
        });

        $unusedOldProofPaths = $oldProofPaths->reject(fn (string $oldPath) => User::query()
            ->where('payment_proof_path', $oldPath)
            ->exists()
            || Transaction::query()
                ->where('payment_proof_path', $oldPath)
                ->exists());

        $paymentProofStorage->deleteOldProofs($unusedOldProofPaths, $path);

        $request->session()->forget('pending_registration');

        $adminNumber = PhoneNumber::normalizeIndonesian(
            (string) AppSetting::getValue('qris_admin_whatsapp', config('services.qris.admin_whatsapp'))
        );
        $referenceCode = $transaction?->reference_code ?? '-';
        $planName = $transaction?->plan_name ?? ($this->resolvePlanForJenjang($teacher->jenjang)?->name ?? 'Aktivasi Akun Guru');
        $amount = $transaction ? (int) round((float) $transaction->amount) : 0;
        $formattedAmount = 'Rp'.number_format($amount, 0, ',', '.');

        if ($adminNumber !== '') {
            $adminMessage = GuruNotificationTemplates::paymentSubmittedAlert(
                teacherName: $teacher->name,
                schoolName: (string) ($teacher->satuan_pendidikan ?? '-'),
                whatsApp: (string) ($teacher->no_wa ?? '-')
            )."\n\nDetail:\nPaket: {$planName}\nNominal: {$formattedAmount}\nKode Referensi: {$referenceCode}";

            SendWhatsAppBlast::dispatch($adminNumber, $adminMessage)->onQueue('high');
        }

        if (! blank($teacher->no_wa)) {
            $teacherMessage = trim(implode("\n", [
                "Halo {$teacher->name},",
                '',
                'Terima kasih! Bukti pembayaran Anda sudah kami terima dan sedang diproses.',
                "Kode referensi: {$referenceCode}",
                'Kami akan menghubungi Anda kembali setelah akun diaktifkan dan token akses dibuat.',
                '',
                'Salam,',
                'Admin Ujion',
            ]));

            SendWhatsAppBlast::dispatch($teacher->no_wa, $teacherMessage)->onQueue('high');
        }

        return redirect()->route('login')->with('flash', [
            'type' => 'success',
            'title' => 'Bukti pembayaran berhasil dikirim',
            'message' => 'Bukti pembayaran Anda sudah kami terima. Silakan login kembali setelah admin mengirim token akses.',
            'description' => $adminNumber === ''
                ? 'Nomor WhatsApp admin belum dikonfigurasi. Admin bisa mengisinya di menu Superadmin > Keuangan & QR.'
                : 'Notifikasi WhatsApp ke admin dan konfirmasi ke Anda dijadwalkan lewat antrean.',
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

    private function resolveOrCreatePendingTransaction(User $teacher, PricingPlan $plan): Transaction
    {
        $planAmount = $this->sanitizeAmount($plan->price);

        return DB::transaction(function () use ($teacher, $plan, $planAmount): Transaction {
            $transaction = $teacher->transactions()
                ->where('status', Transaction::STATUS_PENDING)
                ->where('amount', $planAmount)
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($transaction) {
                return $transaction;
            }

            // Batalkan transaksi pending lama dengan nominal berbeda agar tidak menumpuk.
            $teacher->transactions()
                ->where('status', Transaction::STATUS_PENDING)
                ->where('amount', '!=', $planAmount)
                ->update([
                    'status' => Transaction::STATUS_FAILED,
                    'rejection_reason' => 'Dibatalkan otomatis karena tarif berubah.',
                    'reviewed_at' => now(),
                ]);

            return $teacher->transactions()->create([
                'pricing_plan_id' => $plan->id,
                'reference_code' => $this->generateReferenceCode(),
                'plan_name' => $plan->name,
                'amount' => $planAmount,
                'status' => Transaction::STATUS_PENDING,
            ]);
        });
    }

    private function sanitizeAmount(string|int|float|null $amount): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $amount) ?? '0';

        return $normalized !== '' ? $normalized : '0';
    }

    private function normalizePhoneNumber(?string $phone): string
    {
        $normalized = PhoneNumber::normalizeIndonesian($phone);

        return PhoneNumber::toLocalFormat($normalized);
    }

    private function generateReferenceCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = 'UJN-'.now()->format('ymd').'-'.strtoupper(Str::random(8));

            if (! Transaction::query()->where('reference_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Gagal generate reference code.');
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
        $query = PricingPlan::query()->where('is_active', true);

        if ($jenjang && Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            $plan = (clone $query)
                ->where('jenjang', $jenjang)
                ->first();

            if ($plan) {
                return $plan;
            }
        }

        // Fallback hanya ke plan global (tanpa jenjang) — jangan pakai tarif jenjang lain.
        if (Schema::hasTable('pricing_plans') && Schema::hasColumn('pricing_plans', 'jenjang')) {
            return (clone $query)
                ->whereNull('jenjang')
                ->first();
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
