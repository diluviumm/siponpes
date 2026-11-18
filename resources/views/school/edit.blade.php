@extends('layouts.app')

@section('title', 'Kelola Sekolah')

@section('page-title', 'Kelola Sekolah')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Data Sekolah', 'url' => route('schools.index')], 'Kelola ' . $school->name]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $school->name }}</h1>
            <p class="page-subtitle">Perbarui informasi sekolah dan atur password admin.</p>
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

    <form action="{{ route('schools.update', $school) }}" method="POST" class="card card-body">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">Nama Sekolah</label>
                <input type="text" name="name" value="{{ old('name', $school->name) }}" class="input" required>
            </div>

            <div>
                <label class="label">Status</label>
                <select name="status" class="select">
                    <option value="active" {{ $school->status === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="pending" {{ $school->status === 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="rejected" {{ $school->status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="label">Alamat</label>
                <input type="text" name="address" value="{{ old('address', $school->address) }}" class="input">
            </div>

            <div>
                <label class="label">No. Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $school->phone) }}" class="input phone-input">
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
            <a href="{{ route('schools.index') }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>

    <div class="card card-body mt-6">
        <h2 class="text-lg font-semibold text-ink mb-1">Buat Akun Admin / Kasir</h2>
        <p class="text-sm text-ink/60 mb-4">
            Tambahkan akun admin sekolah atau kasir kantin. Akun <strong>kasir</strong> hanya dapat mengakses
            Kasir Kantin dan Rekap Penjualan.
        </p>

        @if($school->users()->count() > 0)
            <div class="mb-4 rounded-lg border border-ink/10 bg-cream/60 p-3">
                <p class="text-sm font-medium text-ink mb-2">Akun yang sudah ada:</p>
                <ul class="space-y-1 text-sm text-ink/70">
                    @foreach($school->users as $user)
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate">{{ $user->email }}</span>
                            <span class="shrink-0 text-xs px-2 py-0.5 rounded-full bg-pine-100 text-pine-900">
                                {{ $user->roleLabel() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('schools.create-admin', $school) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
            @csrf
            <div>
                <label class="label">Nama</label>
                <input type="text" name="admin_name" class="input" placeholder="Nama lengkap" required>
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" name="admin_email" class="input" placeholder="contoh@sekolah.sch.id" required>
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
            <div>
                <label class="label">Peran</label>
                <select name="role" class="select">
                    <option value="admin_sekolah">Admin Sekolah</option>
                    <option value="kasir">Kasir (kantin saja)</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-primary btn-md">Buat Akun</button>
            </div>
        </form>
    </div>

    @if($school->users()->first())
        <div class="card card-body mt-6">
            <h2 class="text-lg font-semibold text-ink mb-1">Reset Password Admin</h2>
            <p class="text-sm text-ink/60 mb-4">Akun admin saat ini: <strong>{{ $school->users()->first()->email }}</strong></p>

            <form action="{{ route('schools.reset-password', $school) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
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
                <div class="sm:col-span-2">
                    <button type="submit" class="btn btn-warning btn-md">Reset Password</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Zona Berbahaya: Hapus Permanen --}}
    <div id="hapus-permanen" class="card card-body mt-6 border-rose-200">
        <h2 class="text-lg font-semibold text-rose-700 mb-1">Zona Berbahaya — Hapus Permanen</h2>
        <p class="text-sm text-ink/70 mb-3">
            Tindakan ini <strong>tidak dapat dibatalkan</strong>. Seluruh data berikut milik
            <strong>{{ $school->name }}</strong> akan dihapus selamanya:
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-3 text-center">
                <p class="text-2xl font-display font-semibold text-rose-700 tabular-nums">{{ $school->santris_count }}</p>
                <p class="text-xs text-ink/60">Santri</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-3 text-center">
                <p class="text-2xl font-display font-semibold text-rose-700 tabular-nums">{{ $school->kelas_count }}</p>
                <p class="text-xs text-ink/60">Kelas</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-3 text-center">
                <p class="text-2xl font-display font-semibold text-rose-700 tabular-nums">{{ $school->journals_count }}</p>
                <p class="text-xs text-ink/60">Jurnal</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-3 text-center">
                <p class="text-2xl font-display font-semibold text-rose-700 tabular-nums">{{ $school->users_count }}</p>
                <p class="text-xs text-ink/60">Akun Admin</p>
            </div>
        </div>

        @if($school->status === 'active')
            <div class="flash flash-success mb-4">
                Tips: jika hanya ingin menghentikan sementara, gunakan tombol <strong>Nonaktifkan</strong> di halaman daftar — data tetap aman.
            </div>
        @endif

        <form action="{{ route('schools.destroy', $school) }}" method="POST" class="max-w-md space-y-3">
            @csrf
            @method('DELETE')
            <div>
                <label class="label">Ketik <span class="font-mono font-bold">HAPUS</span> untuk konfirmasi</label>
                <input type="text" name="confirm" class="input" placeholder="HAPUS" required autocomplete="off">
            </div>
            <button type="submit" class="btn btn-danger btn-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Permanen Sekolah
            </button>
        </form>
    </div>
</div>
@endsection
