@extends('layouts.app')

@section('title', 'Tambah Tarif SPP')

@section('page-title', 'Tambah Tarif SPP')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() && $school ? ['school_id' => $school->id] : [])],
        ['label' => 'Tarif SPP', 'url' => route('spp_tariffs.index', auth()->user()->isSuperadmin() && $school ? ['school_id' => $school->id] : [])],
        'Tambah Tarif',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Tarif SPP</h1>
            <p class="page-subtitle">Tentukan tarif SPP — pilih jenis periode (per bulan / per semester) dan penerapannya.</p>
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

    @if(auth()->user()->isSuperadmin() && ! $school)
        <div class="card card-body mb-6 border-gold-300">
            <label class="label">Pilih Sekolah</label>
            <p class="text-sm text-ink/60 mb-3">Pilih sekolah untuk menambah tarif SPP.</p>
            <form method="GET" action="{{ route('spp_tariffs.create') }}" class="flex gap-3">
                <select name="school_id" class="select" onchange="this.form.submit()">
                    <option value="">-- Pilih Sekolah --</option>
                    @foreach($schools as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('spp_tariffs.index') }}" class="btn btn-ghost btn-md">Batal</a>
            </form>
        </div>
    @else
        <form action="{{ route('spp_tariffs.store') }}" method="POST" class="card card-body" x-data="{ type: '{{ old('type', 'semua') }}' }">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $school->id }}">

            <div class="space-y-4">
                <div>
                    <label class="label">Sekolah</label>
                    <input type="text" value="{{ $school->name }}" class="input bg-cream" disabled>
                </div>

                <div>
                    <label class="label">Terapkan ke</label>
                    <select name="type" x-on:change="type = $event.target.value" class="select">
                        <option value="semua" {{ old('type', 'semua') == 'semua' ? 'selected' : '' }}>Semua Kelas</option>
                        <option value="angkatan" {{ old('type', 'semua') == 'angkatan' ? 'selected' : '' }}>Per Angkatan</option>
                        <option value="kelas" {{ old('type', 'semua') == 'kelas' ? 'selected' : '' }}>Per Kelas</option>
                    </select>
                </div>

                <div>
                    <div x-show="type === 'kelas'" x-cloak>
                        <label class="label">Kelas</label>
                        <select name="kelas_id" class="select">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('kelas_id') == $class->id ? 'selected' : '' }}>{{ $class->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="type === 'angkatan'" x-cloak>
                        <label class="label">Tingkat / Angkatan</label>
                        <select name="angkatan" class="select">
                            <option value="">-- Pilih Tingkat --</option>
                            @foreach($angkatans as $t)
                                <option value="{{ $t }}" {{ old('angkatan') == $t ? 'selected' : '' }}>Kelas {{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Jenis Periode</label>
                    <select name="periode_type" class="select">
                        <option value="bulanan" {{ old('periode_type', 'bulanan') == 'bulanan' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="semester" {{ old('periode_type', 'bulanan') == 'semester' ? 'selected' : '' }}>Per Semester</option>
                    </select>
                </div>

                <div>
                    <x-academic-year-input :value="old('academic_year')" />
                </div>

                <div>
                    <label class="label">Jumlah (Rp)</label>
                    <input type="text" inputmode="numeric" name="amount" value="{{ old('amount') }}" class="input rupiah-input" required>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn btn-primary btn-md">Simpan</button>
                <a href="{{ route('spp_tariffs.index') }}" class="btn btn-ghost btn-md">Kembali</a>
            </div>
        </form>
    @endif
</div>
@endsection
