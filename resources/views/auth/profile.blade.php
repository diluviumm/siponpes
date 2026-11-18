@extends('layouts.app')

@section('title', 'Profil Saya')

@section('page-title', 'Profil Saya')

@section('content')
<div class="max-w-lg mx-auto">

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Profil Saya']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Profil Saya</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Perbarui informasi akun Anda.</p>
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

    <div class="card card-body mb-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-ink/50 uppercase tracking-wider text-xs font-semibold">Peran</dt>
                <dd class="font-medium text-pine-900 mt-1">{{ $user->roleLabel() }}</dd>
            </div>
            <div>
                <dt class="text-ink/50 uppercase tracking-wider text-xs font-semibold">Sekolah</dt>
                <dd class="font-medium text-pine-900 mt-1">
                    {{ $user->school ? $user->school->name : '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-ink/50 uppercase tracking-wider text-xs font-semibold">Bergabung Sejak</dt>
                <dd class="font-medium text-pine-900 mt-1">{{ $user->created_at->locale('id')->translatedFormat('d F Y') }}</dd>
            </div>
            <div>
                <dt class="text-ink/50 uppercase tracking-wider text-xs font-semibold">Terakhir Login</dt>
                <dd class="font-medium text-pine-900 mt-1">
                    {{ $user->last_login_at ? $user->last_login_at->locale('id')->translatedFormat('d F Y, H:i') : 'Belum pernah login' }}
                </dd>
            </div>
            @if($user->email_verified_at)
                <div>
                    <dt class="text-ink/50 uppercase tracking-wider text-xs font-semibold">Email</dt>
                    <dd class="font-medium text-pine-900 mt-1">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-ink/50 uppercase tracking-wider text-xs font-semibold">Status Email</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-pine-100 text-pine-900">Terverifikasi</span>
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    <form action="{{ route('profile.update') }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="label">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required autofocus>
            </div>

            <div>
                <label class="label">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                <p class="text-xs text-ink/50 mt-1">Mengganti email akan menandai email Anda belum terverifikasi — link verifikasi baru akan dikirim.</p>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
            <a href="{{ route('password.change') }}" class="btn btn-ghost btn-md">Ganti Password</a>
        </div>
    </form>
</div>
@endsection