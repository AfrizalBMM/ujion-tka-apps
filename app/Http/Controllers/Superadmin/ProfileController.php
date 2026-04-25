<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();

        return view('superadmin.profile', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        $user->update($data);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Profil superadmin berhasil diperbarui.',
        ]);
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $user = Auth::user();
        abort_unless($user, 401);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Password superadmin berhasil diganti.',
        ]);
    }
}
