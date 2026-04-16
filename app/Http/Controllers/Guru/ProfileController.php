<?php
namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller {
    public function show(): View {
        $user = Auth::user();

        return view('guru.profile', compact('user'));
    }

    public function update(Request $request) {
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'jenjang' => 'required|in:SD,SMP',
            'tingkat' => 'required|string|max:10',
            'satuan_pendidikan' => 'required|string|max:255',
            'no_wa' => ['required', 'string', 'max:20', Rule::unique('users', 'no_wa')->ignore($user->id)],
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        $user->update($data);

        return back()->with('flash', ['type'=>'success','message'=>'Profil berhasil diperbarui.']);
    }

    public function password(Request $request) {
        $request->validate([
            'password' => 'required|confirmed|min:6',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('flash', ['type'=>'success','message'=>'Password berhasil diganti.']);
    }
}
