@extends('layouts.app')

@section('title', 'Tambah Sekolah')

@section('page-title', 'Tambah Sekolah')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Data Sekolah', 'url' => route('schools.index')], 'Tambah Sekolah']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Sekolah</h1>
            <p class="page-subtitle">Tambah sekolah baru secara manual, beserta akun adminnya (opsional).</p>
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

    <form action="{{ route('schools.store') }}" method="POST" class="card card-body">
        @csrf

        <h3 class="text-base font-semibold text-pine-900 mb-3">Informasi Sekolah</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">Nama Sekolah <span class="text-rose-600">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="input" required>
            </div>

            <div>
                <label class="label">Status</label>
                <select name="status" class="select">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="label">Alamat</label>
                <input type="text" name="address" value="{{ old('address') }}" class="input">
            </div>

            <div>
                <label class="label">No. Telepon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="input phone-input">
            </div>
        </div>

        <div class="my-6 border-t border-ink/10 pt-6">
            <h3 class="text-base font-semibold text-pine-900 mb-1">Akun Admin Sekolah</h3>
            <p class="text-sm text-ink/60 mb-4">Isi jika ingin langsung membuat akun admin untuk sekolah ini (opsional).</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Nama Admin</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="input" placeholder="Nama pengelola">
                </div>

                <div>
                    <label class="label">Email Admin</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" class="input" placeholder="admin@sekolah.sch.id">
                </div>

                <div>
                    <label class="label">Password</label>
                    <div class="relative password-field">
                        <input type="password" name="admin_password" class="input pr-11" placeholder="8–255 karakter">
                        <x-password-toggle />
                    </div>
                </div>

                <div>
                    <label class="label">Ulangi Password</label>
                    <div class="relative password-field">
                        <input type="password" name="admin_password_confirmation" class="input pr-11">
                        <x-password-toggle />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan</button>
            <a href="{{ route('schools.index') }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
