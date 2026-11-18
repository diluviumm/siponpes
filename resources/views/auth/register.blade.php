@extends('layouts.auth')

@section('title', 'Daftar Sekolah — SIPONPES')

@section('content')
    <div class="w-full max-w-lg">

        <div class="card card-body">
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-pine-700 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-gold-300" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-semibold text-pine-900">Daftar Sekolah Baru</h1>
                <p class="text-sm text-ink/60 mt-1">Setelah mendaftar, akun Anda menunggu persetujuan admin.</p>
                <x-page-title-rule width="w-full mt-4" />
            </div>

            @if($errors->any())
                <div class="flash flash-error mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-pine-50 border border-pine-100 rounded-lg px-4 py-3 text-sm text-pine-900 mb-5">
                <strong>Alur aktivasi:</strong> 1) Daftar &rarr; 2) verifikasi email &rarr; 3) disetujui superadmin &rarr; 4) login.
                Verifikasi email bisa dilakukan lebih dulu, bahkan saat sekolah masih menunggu.
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                @csrf

                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label" for="school_name">Nama Sekolah</label>
                        <input id="school_name" type="text" name="school_name" value="{{ old('school_name') }}"
                               class="input" placeholder="Mis. Ponpes Darul Ulum" required autofocus>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="address">Alamat Sekolah</label>
                        <input id="address" type="text" name="address" value="{{ old('address') }}"
                               class="input" placeholder="Alamat lengkap">
                    </div>

                    <div>
                        <label class="label" for="phone">No. Telepon</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                               class="input phone-input" placeholder="08xxxxxxxxxx / +628xxxxxxxxxx" maxlength="15">
                    </div>

                    <div>
                        <label class="label" for="name">Nama Admin</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                               class="input" placeholder="Nama pengelola" required>
                    </div>

                    <div>
                        <label class="label" for="email">Email Admin</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               class="input" placeholder="admin@sekolah.sch.id" required>
                    </div>

                    <div>
                        <label class="label" for="password">Password</label>
                        <div class="relative password-field">
                            <input id="password" type="password" name="password"
                                   class="input pr-11" placeholder="8–255 karakter" required>
                            <x-password-toggle />
                        </div>
                    </div>

                    <div>
                        <label class="label" for="password_confirmation">Ulangi Password</label>
                        <div class="relative password-field">
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   class="input pr-11" placeholder="Ulangi password" required>
                            <x-password-toggle />
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-md w-full justify-center">
                    Daftar Sekolah
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-ink/60 mt-6">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-pine-700 font-medium hover:underline">Masuk</a>
        </p>
    </div>
@endsection
