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
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        $avatarUrl = $this->avatarUrl($user);
        $initials = collect(preg_split('/\s+/', trim((string) $user->name)))
            ->filter()
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $joinedAt = $user->created_at?->translatedFormat('d F Y');

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'no_wa' => $user->no_wa,
            'satuan_pendidikan' => $user->satuan_pendidikan,
            'jenjang' => $user->jenjang,
            'google_connected' => (bool) $user->google_id,
            'access_token' => $user->access_token,
        ];

        return Inertia::render('Guru/Profile', [
            'user' => $userData,
            'avatarUrl' => $avatarUrl,
            'initials' => $initials,
            'joinedAt' => $joinedAt,
        ]);
    }

    public function edit(): Response
    {
        $user = Auth::user();

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'jenjang' => $user->jenjang,
            'satuan_pendidikan' => $user->satuan_pendidikan,
            'no_wa' => $user->no_wa,
            'has_avatar' => (bool) $user->avatar,
        ];

        return Inertia::render('Guru/ProfileEdit', [
            'user' => $userData,
            'avatarUrl' => $this->avatarUrl($user),
        ]);
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

    private function avatarUrl(User $user): string
    {
        if ($user->avatar) {
            return asset('storage/'.$user->avatar);
        }

        return $user->avatar_url
            ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'Guru').'&background=0f766e&color=ffffff&size=256';
    }
}
