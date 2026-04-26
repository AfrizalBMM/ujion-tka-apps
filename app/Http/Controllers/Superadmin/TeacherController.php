<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\GuruNotificationTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function activate(User $teacher): RedirectResponse
    {
        if ($teacher->payment_status === User::PAYMENT_SUBMITTED) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Gunakan review pembayaran',
                'message' => 'Guru ini sudah mengirim bukti pembayaran. Gunakan aksi setujui pembayaran agar status review tetap konsisten.',
            ]);
        }

        $token = \App\Support\TokenGenerator::uniqueTeacherToken();

        $updateData = [
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'payment_status' => User::PAYMENT_APPROVED,
            'payment_verified_at' => now(),
            'payment_reviewed_by' => Auth::id(),
            'payment_rejection_reason' => null,
            'access_token' => $token,
        ];

        $teacher->update($updateData);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Akun guru berhasil diaktifkan',
            'message' => 'Guru sekarang sudah bisa masuk ke sistem menggunakan nama terdaftar dan token akses di bawah ini.',
            'description' => 'Bagikan token ini lewat kanal yang aman. Token hanya ditampilkan satu kali pada notifikasi ini.',
            'token' => $token,
            'token_label' => 'Token akses untuk dibagikan',
            'copy_block' => GuruNotificationTemplates::activationToken($teacher->name, $token),
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    public function suspend(User $teacher): RedirectResponse
    {
        $teacher->update([
            'account_status' => User::STATUS_SUSPEND,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Akses guru berhasil ditangguhkan',
            'message' => 'Akun ini tidak bisa dipakai masuk sampai diaktifkan kembali atau token baru diberikan.',
        ]);
    }

    public function refreshToken(User $teacher): RedirectResponse
    {
        $token = \App\Support\TokenGenerator::uniqueTeacherToken();

        $teacher->update([
            'access_token' => $token,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Token akses berhasil diperbarui',
            'message' => 'Gunakan token baru di bawah ini untuk dikirim ke guru. Token lama sebaiknya dianggap tidak berlaku lagi.',
            'description' => 'Pastikan guru memakai token terbaru saat login.',
            'token' => $token,
            'token_label' => 'Token akses terbaru',
            'copy_block' => GuruNotificationTemplates::activationToken($teacher->name, $token),
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    public function approvePayment(User $teacher, \App\Services\PaymentApprovalService $paymentService): RedirectResponse
    {
        if (blank($teacher->payment_proof_path) && $teacher->payment_status !== User::PAYMENT_SUBMITTED) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Pembayaran belum bisa disetujui',
                'message' => 'Guru ini belum mengirim bukti pembayaran. Minta guru mengunggah bukti terlebih dahulu.',
            ]);
        }

        $token = $paymentService->approve($teacher);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Pembayaran berhasil disetujui',
            'message' => 'Akun guru sudah aktif dan token akses siap dikirim.',
            'description' => 'Gunakan template di bawah ini untuk mengirim konfirmasi pembayaran dan token akses ke guru.',
            'token' => $token,
            'token_label' => 'Token akses',
            'copy_block' => GuruNotificationTemplates::paymentApproved($teacher->name, $token),
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    public function rejectPayment(Request $request, User $teacher, \App\Services\PaymentApprovalService $paymentService): RedirectResponse
    {
        if (blank($teacher->payment_proof_path) && $teacher->payment_status !== User::PAYMENT_SUBMITTED) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Pembayaran belum bisa ditolak',
                'message' => 'Belum ada bukti pembayaran yang bisa direview untuk guru ini.',
            ]);
        }

        $validated = $request->validate([
            'payment_rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $paymentService->reject($teacher, $validated['payment_rejection_reason']);

        return back()->with('flash', [
            'type' => 'warning',
            'title' => 'Pembayaran ditandai perlu perbaikan',
            'message' => 'Guru telah ditandai perlu mengirim ulang atau memperjelas pembayaran.',
            'copy_block' => GuruNotificationTemplates::paymentRejected(
                $teacher->name,
                $validated['payment_rejection_reason']
            ),
            'copy_block_label' => 'Template pesan WhatsApp',
        ]);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->string('q'));
        $paymentStatus = (string) $request->string('payment_status');
        $accountStatus = (string) $request->string('account_status');

        $teachersQuery = User::query()
            ->where('role', User::ROLE_GURU);

        if ($search !== '') {
            $teachersQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('no_wa', 'like', "%{$search}%")
                    ->orWhere('satuan_pendidikan', 'like', "%{$search}%");
            });
        }

        if (in_array($paymentStatus, [
            User::PAYMENT_AWAITING,
            User::PAYMENT_SUBMITTED,
            User::PAYMENT_APPROVED,
            User::PAYMENT_REJECTED,
        ], true)) {
            $teachersQuery->where('payment_status', $paymentStatus);
        }

        if (in_array($accountStatus, [
            User::STATUS_PENDING,
            User::STATUS_ACTIVE,
            User::STATUS_SUSPEND,
        ], true)) {
            $teachersQuery->where('account_status', $accountStatus);
        }

        $teachers = $teachersQuery
            ->orderByRaw("case when payment_status = 'submitted' then 0 when payment_status = 'rejected' then 1 when payment_status = 'awaiting_payment' then 2 else 3 end")
            ->latest()
            ->get();

        $paymentSummary = [
            User::PAYMENT_SUBMITTED => User::query()->where('role', User::ROLE_GURU)->where('payment_status', User::PAYMENT_SUBMITTED)->count(),
            User::PAYMENT_REJECTED => User::query()->where('role', User::ROLE_GURU)->where('payment_status', User::PAYMENT_REJECTED)->count(),
            User::PAYMENT_AWAITING => User::query()->where('role', User::ROLE_GURU)->where('payment_status', User::PAYMENT_AWAITING)->count(),
            User::PAYMENT_APPROVED => User::query()->where('role', User::ROLE_GURU)->where('payment_status', User::PAYMENT_APPROVED)->count(),
        ];

        $notificationTemplates = GuruNotificationTemplates::library();

        return view('superadmin.teachers', compact(
            'teachers',
            'notificationTemplates',
            'paymentSummary',
            'search',
            'paymentStatus',
            'accountStatus',
        ));
    }

}
