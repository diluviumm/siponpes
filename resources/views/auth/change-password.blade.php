@extends('layouts.app')

@section('title', 'Ganti Password')

@section('page-title', 'Ganti Password')

@section('content')
<div class="max-w-lg mx-auto">

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Ganti Password']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Ganti Password</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Perbarui password akun Anda.</p>
        </div>
    </div>

    @if($errors->any())
        <x-flash type="error">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-flash>
    @endif

    <form action="{{ route('password.update') }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="label">Password Saat Ini</label>
                <div class="relative password-field">
                    <input type="password" name="current_password" class="input pr-11" required autocomplete="current-password">
                    <x-password-toggle />
                </div>
            </div>

            <div>
                <label class="label">Password Baru</label>
                <div class="relative password-field">
                    <input type="password" name="password" class="input pr-11" placeholder="8–255 karakter" required autocomplete="new-password">
                    <x-password-toggle />
                </div>
            </div>

            <div>
                <label class="label">Ulangi Password Baru</label>
                <div class="relative password-field">
                    <input type="password" name="password_confirmation" class="input pr-11" required autocomplete="new-password">
                    <x-password-toggle />
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan Password</button>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
