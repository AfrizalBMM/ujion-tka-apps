<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();

        return view('guru.profile', compact('user'));
    }

    public function edit(): View
    {
        $user = Auth::user();

        return view('guru.profile-edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => 'nullable|image|max:2048',
        ];

        if (Schema::hasColumn('users', 'satuan_pendidikan')) {
            $rules['satuan_pendidikan'] = 'required|string|max:255';
        }

        if (Schema::hasColumn('users', 'no_wa')) {
            $rules['no_wa'] = ['required', 'string', 'max:20'];
        }

        $data = $request->validate($rules);

        if (array_key_exists('no_wa', $data)) {
            $normalized = PhoneNumber::normalizeIndonesian($data['no_wa']);
            $data['no_wa'] = PhoneNumber::toLocalFormat($normalized);

            $duplicateWhatsapp = User::query()
                ->where('id', '!=', $user->id)
                ->whereIn('no_wa', PhoneNumber::variants($data['no_wa']))
                ->exists();

            if ($duplicateWhatsapp) {
                return back()
                    ->withErrors([
                        'no_wa' => 'Nomor WhatsApp ini sudah terdaftar. Silakan gunakan nomor lain.',
                    ])
                    ->withInput();
            }
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        $user->update($data);

        return redirect()->route('guru.profile')->with('flash', ['type' => 'success', 'message' => 'Profil berhasil diperbarui.']);
    }

    public function deleteAvatar(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return redirect()->route('guru.profile.edit')->with('flash', ['type' => 'success', 'message' => 'Foto profil berhasil dihapus.']);
    }

    public function password(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8', 'different:access_token'],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.different' => 'Password tidak boleh sama dengan token akses Anda.',
        ]);

        $user = Auth::user();
        abort_unless($user, 401);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Password berhasil disimpan.']);
    }
}
