@extends('layouts.app')

@section('title', 'Tambah Kelas')

@section('page-title', 'Tambah Kelas')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() && $school ? ['school_id' => $school->id] : [])],
        ['label' => 'Data Kelas', 'url' => route('kelas.index', auth()->user()->isSuperadmin() && $school ? ['school_id' => $school->id] : [])],
        'Tambah Kelas',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Kelas</h1>
            <p class="page-subtitle">Buat kelas baru beserta wali kelas dan kapasitas.</p>
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
            <p class="text-sm text-ink/60 mb-3">Pilih sekolah tempat kelas ini dibuat.</p>
            <form method="GET" action="{{ route('kelas.create') }}" class="flex gap-3">
                <select name="school_id" class="select" onchange="this.form.submit()">
                    <option value="">-- Pilih Sekolah --</option>
                    @foreach($schools as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('kelas.index') }}" class="btn btn-ghost btn-md">Batal</a>
            </form>
        </div>
    @else
        <form action="{{ route('kelas.store') }}" method="POST" class="card card-body">
            @csrf
            @if(auth()->user()->isSuperadmin())
                <input type="hidden" name="branch_id" value="{{ $school->id }}">
            @else
                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label">Sekolah</label>
                    <input type="text" value="{{ $school->name }}" class="input bg-cream" disabled>
                </div>

                <div>
                    <label class="label">Nama Kelas</label>
                    <input type="text" name="nama_kelas" value="{{ old('nama_kelas') }}" class="input" required>
                </div>

                <div>
                    <label class="label">Wali Kelas</label>
                    <input type="text" name="wali_kelas" value="{{ old('wali_kelas') }}" class="input" required>
                </div>

                <div>
                    <label class="label">Kapasitas</label>
                    <input type="number" name="kapasitas" value="{{ old('kapasitas') }}" class="input" required>
                </div>

                <div class="md:col-span-2">
                    <label class="label">Keterangan</label>
                    <textarea name="keterangan" rows="3" class="textarea">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn btn-primary btn-md">Simpan</button>
                <a href="{{ route('kelas.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
            </div>
        </form>
    @endif
</div>
@endsection
