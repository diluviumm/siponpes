<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if ($user->isStaff() && (! $user->school || ! $user->school->isActive())) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun tidak aktif atau sekolah belum disetujui. Hubungi admin untuk mengaktifkan sekolah Anda.',
            ]);
        }

        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'action' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'summary' => $user->email,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
