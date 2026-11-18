@extends('layouts.app')

@section('title', 'Edit Tarif SPP')

@section('page-title', 'Edit Tarif SPP')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [])],
        ['label' => 'Tarif SPP', 'url' => route('spp_tariffs.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [])],
        'Edit Tarif',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Tarif SPP</h1>
            <p class="page-subtitle">Perbarui tarif SPP — jenis periode (bulanan/semester) dan cakupan kelas — {{ $school->name }}.</p>
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

    <form action="{{ route('spp_tariffs.update', $spp_tariff) }}" method="POST" class="card card-body" x-data="{ type: '{{ old('type', $spp_tariff->type ?? 'semua') }}' }}">
        @csrf
        @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="label">Sekolah</label>
                    <input type="text" value="{{ $school->name }}" class="input bg-cream" disabled>
                </div>

                <div>
                    <label class="label">Terapkan ke</label>
                    <select name="type" x-on:change="type = $event.target.value" class="select">
                        <option value="semua" {{ old('type', $spp_tariff->type ?? 'semua') == 'semua' ? 'selected' : '' }}>Semua Kelas</option>
                        <option value="angkatan" {{ old('type', $spp_tariff->type ?? 'semua') == 'angkatan' ? 'selected' : '' }}>Per Angkatan</option>
                        <option value="kelas" {{ old('type', $spp_tariff->type ?? 'semua') == 'kelas' ? 'selected' : '' }}>Per Kelas</option>
                    </select>
                </div>

                <div>
                    <div x-show="type === 'kelas'" x-cloak>
                        <label class="label">Kelas</label>
                        <select name="kelas_id" class="select">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('kelas_id', $spp_tariff->kelas_id) == $class->id ? 'selected' : '' }}>{{ $class->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="type === 'angkatan'" x-cloak>
                        <label class="label">Tingkat / Angkatan</label>
                        <select name="angkatan" class="select">
                            <option value="">-- Pilih Tingkat --</option>
                            @foreach($angkatans as $t)
                                <option value="{{ $t }}" {{ old('angkatan', $spp_tariff->angkatan) == $t ? 'selected' : '' }}>Kelas {{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Jenis Periode</label>
                    <select name="periode_type" class="select">
                        <option value="bulanan" {{ old('periode_type', $spp_tariff->periode_type ?? 'bulanan') == 'bulanan' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="semester" {{ old('periode_type', $spp_tariff->periode_type ?? 'bulanan') == 'semester' ? 'selected' : '' }}>Per Semester</option>
                    </select>
                </div>

                <div>
                    <x-academic-year-input :value="old('academic_year', $spp_tariff->academic_year)" />
                </div>

                <div>
                    <label class="label">Jumlah (Rp)</label>
                    <input type="text" inputmode="numeric" name="amount" value="{{ old('amount', $spp_tariff->amount) }}" class="input rupiah-input" required>
                </div>
            </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
            <a href="{{ route('spp_tariffs.index') }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
