@extends('layouts.app')

@section('title', 'Profil Sekolah')

@section('page-title', 'Profil Sekolah')

@section('content')
<div class="max-w-lg mx-auto">

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Profil Sekolah']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Profil Sekolah</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Perbarui identitas sekolah Anda. Perubahan langsung tampil di laporan dan header.</p>
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

    <form action="{{ route('school.profile.update') }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="label">Nama Sekolah</label>
                <input type="text" name="name" value="{{ old('name', $school->name) }}" class="input" required maxlength="255">
            </div>

            <div>
                <label class="label">Alamat</label>
                <textarea name="address" rows="3" class="textarea" maxlength="500" placeholder="Alamat lengkap sekolah">{{ old('address', $school->address) }}</textarea>
            </div>

            <div>
                <label class="label">No. Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $school->phone) }}"
                       class="input phone-input" maxlength="15" placeholder="08xxxxxxxxxx / +628xxxxxxxxxx">
            </div>

            @php
                $statusInfo = match (true) {
                    $school->isActive() => ['box' => 'bg-pine-50 border-pine-200', 'badge' => 'badge-green', 'label' => 'Aktif', 'desc' => 'Sekolah Anda aktif. Semua fitur dapat digunakan.'],
                    $school->isPending() => ['box' => 'bg-gold-50 border-gold-300', 'badge' => 'badge-yellow', 'label' => 'Menunggu', 'desc' => 'Sekolah menunggu persetujuan superadmin.'],
                    default => ['box' => 'bg-rose-50 border-rose-200', 'badge' => 'badge-red', 'label' => 'Nonaktif', 'desc' => 'Sekolah dinonaktifkan oleh superadmin. Hubungi admin untuk mengaktifkan kembali.'],
                };
            @endphp
            <div class="rounded-lg border px-4 py-3 {{ $statusInfo['box'] }}">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-ink/70">Status Sekolah</span>
                    <span class="badge {{ $statusInfo['badge'] }}">{{ $statusInfo['label'] }}</span>
                </div>
                <p class="text-sm text-ink/60 mt-1">{{ $statusInfo['desc'] }}</p>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
