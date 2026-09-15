<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Jenjang;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected array $guruRouteLabels = [
        'login' => 'Login berhasil',
        'guru.profile.update' => 'Profil diperbarui',
        'guru.profile.password' => 'Password diubah',
        'guru.profile.avatar.delete' => 'Foto profil dihapus',
        'guru.exams.join' => 'Memulai simulasi ujian',
        'guru.chat.store' => 'Pesan terkirim ke admin',
        'guru.personal-questions.store' => 'Soal pribadi ditambahkan',
        'guru.personal-questions.update' => 'Soal pribadi diperbarui',
        'guru.personal-questions.destroy' => 'Soal pribadi dihapus',
        'guru.soal.store' => 'Soal paket ditambahkan',
        'guru.soal.update' => 'Soal paket diperbarui',
        'guru.soal.destroy' => 'Soal paket dihapus',
        'guru.soal.import-ujion' => 'Soal diimpor dari Ujion',
        'guru.teks-bacaan.store' => 'Teks bacaan ditambahkan',
        'guru.teks-bacaan.destroy' => 'Teks bacaan dihapus',
        'guru.materials.bookmark' => 'Materi disimpan',
        'guru.materials.unbookmark' => 'Bookmark materi dihapus',
        'guru.soal-ujion.bookmark' => 'Soal Ujion disimpan',
        'guru.soal-ujion.unbookmark' => 'Bookmark soal dihapus',
        'guru.mapel.update' => 'Konfigurasi mapel diperbarui',
    ];

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_filter([
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'no_wa' => $user->no_wa,
                    'role' => $user->role,
                    'jenjang' => $user->jenjang,
                    'satuan_pendidikan' => $user->satuan_pendidikan,
                    'account_status' => $user->account_status,
                    'payment_status' => $user->payment_status,
                    'access_token' => $user->access_token,
                    'avatar_url' => $user->avatar_url,
                ] : null,
            ],
            'csrf_token' => csrf_token(),
            'flash' => fn () => session('flash'),
            'status' => fn () => session('status'),
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
            'routeName' => $request->route()?->getName(),
            'guruLayout' => fn () => $user && $user->role === User::ROLE_GURU ? $this->guruLayoutProps($user) : null,
            'superadminLayout' => fn () => $user && $user->role === User::ROLE_SUPERADMIN ? [
                'pendingPaymentCount' => Transaction::where('status', Transaction::STATUS_PENDING)->count(),
            ] : null,
        ]);
    }

    protected function guruLayoutProps(User $user): array
    {
        $paymentLocked = $user->account_status === User::STATUS_PENDING;

        $paymentInfo = null;
        if ($paymentLocked) {
            $plan = PricingPlan::resolveForJenjang($user->jenjang);

            $paymentInfo = [
                'jenjang' => $user->jenjang,
                'jenjangLabel' => Jenjang::where('kode', $user->jenjang)->value('nama'),
                'planName' => $plan?->name,
                'planDescription' => $plan?->description ?: $plan?->subtitle,
                'amount' => $plan?->price,
            ];
        }

        $activeDokuInvoice = null;
        if ($paymentLocked) {
            $activeDokuInvoice = $user->transactions()
                ->where('status', Transaction::STATUS_PENDING)
                ->where('payment_method', Transaction::PAYMENT_METHOD_DOKU)
                ->latest()
                ->value('doku_invoice_number');
        }

        $dokuConfig = $paymentLocked ? [
            'startUrl' => route('payments.doku.start'),
            'statusUrl' => route('payments.doku.status'),
            'finishUrl' => route('payments.doku.finish'),
            'csrfToken' => csrf_token(),
            'pollOrderId' => $activeDokuInvoice,
        ] : null;

        $notifLogs = collect();
        try {
            $notifLogs = AuditLog::where('user_id', $user->id)
                ->where('method', '!=', 'GET')
                ->whereNotNull('route_name')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'label' => $this->guruRouteLabels[$log->route_name] ?? null,
                    'created_at' => $log->created_at?->diffForHumans(),
                ])
                ->filter(fn (array $log) => $log['label'] !== null)
                ->values();
        } catch (\Throwable) {
        }

        return [
            'paymentLocked' => $paymentLocked,
            'paymentInfo' => $paymentInfo,
            'waGroupLink' => AppSetting::getValue('wa_group_link'),
            'dokuConfig' => $dokuConfig,
            'notifLogs' => $notifLogs,
        ];
    }
}
