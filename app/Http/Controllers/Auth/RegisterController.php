<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        if ($request->filled('website')) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $value)) {
                    $fail('Format nomor telepon tidak valid. Gunakan 0 atau +62 diikuti 8–13 digit, contoh: 081234567890.');
                }
            }],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
        ]);

        $school = DB::transaction(function () use ($data) {
            $school = School::create([
                'name' => $data['school_name'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => School::STATUS_PENDING,
                'api_key' => School::generateApiKey(),
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_SCHOOL_ADMIN,
                'branch_id' => $school->id,
            ]);

            $user->sendEmailVerificationNotification();

            return $school;
        });

        return redirect()->route('login')
            ->with('success', "Pendaftaran sekolah {$school->name} berhasil. Akun Anda menunggu persetujuan admin.");
    }
}
