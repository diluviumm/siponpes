@extends('layouts.auth')

@section('title', 'Atur Ulang Password — SIPONPES')

@section('content')
    <div class="w-full max-w-md">

        <div class="card card-body">
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-pine-700 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-gold-300" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-semibold text-pine-900">Atur Ulang Password</h1>
                <p class="text-sm text-ink/60 mt-1">Buat password baru untuk akun Anda.</p>
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

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}"
                           class="input" placeholder="nama@sekolah.sch.id" required autofocus
                           autocomplete="username">
                </div>

                <div>
                    <label class="label" for="password">Password Baru</label>
                    <div class="relative password-field">
                        <input id="password" type="password" name="password"
                               class="input pr-11" placeholder="8–255 karakter" required
                               autocomplete="new-password">
                        <x-password-toggle />
                    </div>
                </div>

                <div>
                    <label class="label" for="password_confirmation">Ulangi Password</label>
                    <div class="relative password-field">
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="input pr-11" placeholder="Ulangi password" required
                               autocomplete="new-password">
                        <x-password-toggle />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-md w-full justify-center">
                    Simpan Password Baru
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-ink/60 mt-6">
            Ingat password?
            <a href="{{ route('login') }}" class="text-pine-700 font-medium hover:underline">Masuk</a>
        </p>
    </div>
@endsection
