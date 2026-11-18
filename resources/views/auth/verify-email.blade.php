@extends('layouts.auth')

@section('title', 'Verifikasi Email — SIPONPES')

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
                <h1 class="font-display text-2xl font-semibold text-pine-900">Verifikasi Email Anda</h1>
                <p class="text-sm text-ink/60 mt-1">
                    Tautan verifikasi telah dikirim ke <strong>{{ auth()->user()->email }}</strong>.
                    Klik tautan tersebut untuk mengaktifkan akun.
                </p>
                <x-page-title-rule width="w-full mt-4" />
            </div>

            <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                @csrf

                <button type="submit" class="btn btn-primary btn-md w-full justify-center">
                    Kirim Ulang Tautan Verifikasi
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="text-center mt-6">
            @csrf
            <button type="submit" class="text-sm text-ink/60 cursor-pointer">
                <span class="text-pine-700 font-medium hover:underline">Keluar dan kembali ke halaman masuk</span>
            </button>
        </form>
    </div>
@endsection
