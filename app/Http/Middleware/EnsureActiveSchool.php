<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, [User::ROLE_SCHOOL_ADMIN, User::ROLE_CASHIER], true)) {
            $school = $user->school;

            if (! $school || ! $school->isActive()) {
                $message = match (true) {
                    $school?->isPending() => 'Sekolah Anda masih menunggu persetujuan superadmin.',
                    $school === null => 'Akun Anda belum terhubung ke sekolah. Hubungi superadmin.',
                    default => 'Sekolah Anda telah dinonaktifkan oleh superadmin. Hubungi admin untuk mengaktifkan kembali.',
                };

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', $message);
            }
        }

        return $next($request);
    }
}
