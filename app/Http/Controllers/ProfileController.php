<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('auth.profile', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('profile.edit')
            ->with('success', $emailChanged
                ? 'Profil berhasil diperbarui. Link verifikasi email baru telah dikirim ke alamat email Anda.'
                : 'Profil berhasil diperbarui.');
    }

    public function editPassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => $request->input('password'),
        ]);

        return redirect()->route('password.change')
            ->with('success', 'Password berhasil diganti.');
    }
}
