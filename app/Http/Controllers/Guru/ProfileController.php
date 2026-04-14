<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller {
    public function show() {
        $user = Auth::user();
        return view('guru.profile', compact('user'));
    }
    public function update(Request $request) {
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'avatar' => 'nullable|image|max:2048',
        ]);
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
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