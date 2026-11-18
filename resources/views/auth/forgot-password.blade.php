@extends('layouts.auth')

@section('title', 'Lupa Password — SIPONPES')

@section('content')
    <div class="w-full max-w-md">

        @if(session('status'))
            <x-flash type="success">{{ session('status') }}</x-flash>
        @endif

        <div class="card card-body">
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-pine-700 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-gold-300" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-semibold text-pine-900">Lupa Password</h1>
                <p class="text-sm text-ink/60 mt-1">Masukkan email Anda, kami kirim tautan untuk mengatur ulang password.</p>
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

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="input" placeholder="nama@sekolah.sch.id" required autofocus
                           autocomplete="username">
                </div>

                <button type="submit" class="btn btn-primary btn-md w-full justify-center">
                    Kirim Tautan Reset
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-ink/60 mt-6">
            Ingat password?
            <a href="{{ route('login') }}" class="text-pine-700 font-medium hover:underline">Masuk</a>
        </p>
    </div>
@endsection
