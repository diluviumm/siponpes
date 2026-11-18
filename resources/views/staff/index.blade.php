@extends('layouts.app')

@section('title', $school ? "Kelola Akun {$school->name}" : 'Kelola Akun')

@section('page-title', $school ? "Kelola Akun {$school->name}" : 'Kelola Akun')

@section('content')

@php
    $isSuper = auth()->user()->isSuperadmin();
@endphp

<x-school-filter :schools="$schools" route="staff.index" :current="$school?->id" />

@if(! $school)

    {{-- Pilih Sekolah (superadmin) --}}
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Kelola Akun']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Akun</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk mengelola akun admin &amp; kasir.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk mengelola akun.
    </div>

@else

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Kelola Akun', 'url' => route('staff.index')] : 'Kelola Akun',
        $isSuper ? $school->name : null,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $school->name }}</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Kelola akun admin & kasir di sekolah ini.</p>
        </div>
    </div>

    {{-- Form buat akun --}}
    @if($errors->any())
        <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-body max-w-2xl mb-6">
        <h2 class="text-lg font-semibold text-ink mb-1">Buat Akun {{ $isSuper ? 'Admin / Kasir' : 'Kasir' }}</h2>
        <p class="text-sm text-ink/60 mb-4">
            @if($isSuper)
                Tambahkan akun admin sekolah atau kasir kantin. Akun <strong>kasir</strong> hanya dapat mengakses Kasir Kantin dan Rekap Penjualan.
            @else
                Tambahkan akun <strong>kasir</strong> untuk kantin. Akun kasir hanya dapat mengakses Kasir Kantin dan Rekap Penjualan.
            @endif
        </p>

        <form action="{{ route('staff.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
            @csrf
            @if($isSuper)
                <input type="hidden" name="branch_id" value="{{ $school->id }}">
            @endif
            <div>
                <label class="label">Nama</label>
                <input type="text" name="admin_name" class="input" placeholder="Nama lengkap" value="{{ old('admin_name') }}" required>
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" name="admin_email" class="input {{ $errors->has('admin_email') ? 'border-rose-500' : '' }}" placeholder="contoh@sekolah.sch.id" value="{{ old('admin_email') }}" required>
                @error('admin_email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Password</label>
                <div class="relative password-field">
                    <input type="password" name="admin_password" class="input pr-11" placeholder="8–255 karakter" required>
                    <x-password-toggle />
                </div>
            </div>
            <div>
                <label class="label">Ulangi Password</label>
                <div class="relative password-field">
                    <input type="password" name="admin_password_confirmation" class="input pr-11" required>
                    <x-password-toggle />
                </div>
            </div>
            @if($isSuper)
                <div>
                    <label class="label">Peran</label>
                    <select name="role" class="select">
                        <option value="admin_sekolah">Admin Sekolah</option>
                        <option value="kasir">Kasir (kantin saja)</option>
                    </select>
                </div>
            @endif
            <div class="{{ $isSuper ? 'sm:col-span-2' : '' }}">
                <button type="submit" class="btn btn-primary btn-md">Buat Akun</button>
            </div>
        </form>
    </div>

    {{-- Pencarian akun --}}
    <form method="GET" class="mt-6 mb-6">
        @if(auth()->user()->isSuperadmin())
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <div class="flex gap-3 flex-wrap">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau email akun..."
                    class="input pl-10">
            </div>
            <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
            @if(request('search'))
                <a href="{{ route('staff.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
            @endif
        </div>
    </form>

    {{-- Daftar akun --}}
    <div class="card card-body mt-6">
        <h2 class="text-lg font-semibold text-ink mb-1">Akun {{ $accounts->count() }}</h2>
        <p class="text-sm text-ink/60 mb-4">Daftar admin & kasir di sekolah ini.</p>

        @forelse($accounts as $user)
            <div class="border-b border-ink/10 last:border-b-0 py-4 first:pt-0 last:pb-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{{ $user->name }}</p>
                        <p class="text-sm text-ink/60">{{ $user->email }}</p>
                    </div>
                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full {{ $user->role === 'kasir' ? 'bg-gold-100 text-gold-800' : 'bg-pine-100 text-pine-900' }}">
                        {{ $user->roleLabel() }}
                    </span>
                </div>

                <div class="mt-3">
                    <details class="group">
                        <summary class="inline-flex items-center gap-1.5 text-sm text-pine-700 hover:text-pine-900 cursor-pointer font-medium">
                            <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Reset Password
                        </summary>
                        <form action="{{ route('staff.reset-password', $user) }}" method="POST" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 items-end max-w-2xl">
                            @csrf
                            <div>
                                <label class="label">Password Baru</label>
                                <div class="relative password-field">
                                    <input type="password" name="password" class="input pr-11" placeholder="8–255 karakter" required>
                                    <x-password-toggle />
                                </div>
                            </div>
                            <div>
                                <label class="label">Ulangi Password</label>
                                <div class="relative password-field">
                                    <input type="password" name="password_confirmation" class="input pr-11" required>
                                    <x-password-toggle />
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-warning btn-md">Reset Password</button>
                            </div>
                        </form>
                    </details>

                    @if($user->role === 'kasir')
                        <form method="POST" action="{{ route('staff.destroy', $user) }}" class="mt-3"
                              data-confirm="Hapus akun kasir {{ $user->name }} ({{ $user->email }})?"
                              data-confirm-label="Hapus">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 text-sm text-rose-700 hover:text-rose-900 cursor-pointer font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus Akun
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-ink/50">
                Belum ada akun admin/kasir di sekolah ini.
            </div>
        @endforelse
    </div>

@endif

@endsection
