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
        $updateData = [
            'role' => User::ROLE_GURU,
            'account_status' => User::STATUS_ACTIVE,
        ];

        // Generate token if not exists
        if (!$teacher->access_token) {
            $updateData['access_token'] = strtoupper(Str::random(10)); // Format lebih pendek & ramah
        }

        $teacher->update($updateData);

        return back()->with('flash', ['type' => 'success', 'message' => 'Guru berhasil diaktifkan. ' . ($teacher->wasRecentlyUpdated && isset($updateData['access_token']) ? 'Token baru: ' . $updateData['access_token'] : '')]);
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
        $token = null;
        for ($i = 0; $i < 5; $i++) {
            $candidate = Str::random(32);
            if (! User::query()->where('access_token', $candidate)->exists()) {
                $token = $candidate;
                break;
            }
        }

        if (! $token) {
            return back()->with('flash', ['type' => 'warning', 'message' => 'Gagal generate token. Coba lagi.']);
        }

        $teacher->update([
            'access_token' => $token,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Token akses guru berhasil diperbarui.']);
    }

    public function index() {
        $teachers = User::where('role', User::ROLE_GURU)->get();
        return view('superadmin.teachers', compact('teachers'));
    }
}
