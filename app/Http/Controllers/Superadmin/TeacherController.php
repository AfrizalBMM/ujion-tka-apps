<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function activate(User $teacher): RedirectResponse
    {
        $token = $teacher->access_token ?: $this->generateUniqueToken();

        $updateData = [
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
            'access_token' => $token,
        ];

        $teacher->update($updateData);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Guru berhasil diaktifkan. Token akses siap digunakan.',
            'token' => $token,
        ]);
    }

    public function suspend(User $teacher): RedirectResponse
    {
        $teacher->update([
            'account_status' => User::STATUS_SUSPEND,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Guru berhasil disuspend.']);
    }

    public function refreshToken(User $teacher): RedirectResponse
    {
        $token = $this->generateUniqueToken();

        $teacher->update([
            'access_token' => $token,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Token akses guru berhasil diperbarui.',
            'token' => $token,
        ]);
    }

    public function index() {
        $teachers = User::where('role', User::ROLE_GURU)->get();
        return view('superadmin.teachers', compact('teachers'));
    }

    private function generateUniqueToken(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $candidate = strtoupper(Str::random(10));
            if (! User::query()->where('access_token', $candidate)->exists()) {
                return $candidate;
            }
        }

        abort(500, 'Gagal generate token unik.');
    }
}
