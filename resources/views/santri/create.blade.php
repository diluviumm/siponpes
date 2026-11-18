@extends('layouts.app')

@section('title', 'Tambah Santri')

@section('page-title', 'Tambah Santri')

@section('content')
<div class="max-w-4xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [])],
        ['label' => 'Data Santri', 'url' => route('santri.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [])],
        'Tambah Santri',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Santri</h1>
            <p class="page-subtitle">Lengkapi identitas santri baru — {{ $school->name }}.</p>
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

    <form action="{{ route('santri.store') }}" method="POST" class="card card-body">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $school->id }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">NIS</label>
                <input type="text" name="nis" value="{{ old('nis') }}" class="input" placeholder="Opsional" maxlength="10">
            </div>

            <div>
                <label class="label">Nama <span class="text-rose-600">*</span></label>
                <input type="text" name="nama" value="{{ old('nama') }}" class="input" required>
            </div>

            <div>
                <label class="label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="select">
                    <option value="">-- Pilih --</option>
                    <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
            </div>

            <div>
                <label class="label">Kelas <span class="text-rose-600">*</span></label>
                <select name="kelas_id" class="select" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($kelas as $item)
                        @php
                            $kapasitas = (int) $item->kapasitas;
                            $terisi = (int) $item->santris_count;
                            $penuh = $kapasitas > 0 && $terisi >= $kapasitas;
                        @endphp
                        <option value="{{ $item->id }}"
                            {{ old('kelas_id') == $item->id ? 'selected' : '' }}
                            @disabled($penuh)>
                            {{ $item->nama_kelas }} ({{ $terisi }}/{{ $kapasitas > 0 ? $kapasitas : '∞' }}){{ $penuh ? ' — Penuh' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('kelas_id')
                    <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}" class="input">
            </div>

            <div>
                <label class="label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="input">
            </div>

            <div>
                <label class="label">No HP Wali</label>
                <input type="text" name="no_hp_wali" value="{{ old('no_hp_wali') }}" class="input phone-input" placeholder="08xxxxxxxxxx / +628xxxxxxxxxx" maxlength="15">
            </div>

            <div>
                <label class="label">Email Wali</label>
                <input type="email" name="email" value="{{ old('email') }}" class="input">
            </div>

            <div>
                <label class="label">Token Kartu Santri</label>
                <input type="text" name="santri_card_token" value="{{ old('santri_card_token') }}" class="input">
            </div>

            <div class="md:col-span-2">
                <label class="label">Alamat</label>
                <textarea name="alamat" rows="3" class="textarea">{{ old('alamat') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan</button>
            <a href="{{ route('santri.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
