@extends('layouts.app')

@section('title', 'Edit Kelas')

@section('page-title', 'Edit Kelas')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [])],
        ['label' => 'Data Kelas', 'url' => route('kelas.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [])],
        'Edit Kelas',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Kelas</h1>
            <p class="page-subtitle">Perbarui informasi kelas {{ $kela->nama_kelas }}.</p>
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

    <form action="{{ route('kelas.update', $kela) }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="label">Sekolah</label>
                <input type="text" value="{{ $school->name ?? '-' }}" class="input bg-cream" disabled>
            </div>

            <div>
                <label class="label">Nama Kelas</label>
                <input type="text" name="nama_kelas" value="{{ old('nama_kelas', $kela->nama_kelas) }}" class="input" required>
            </div>

            <div>
                <label class="label">Wali Kelas</label>
                <input type="text" name="wali_kelas" value="{{ old('wali_kelas', $kela->wali_kelas) }}" class="input" required>
            </div>

            <div>
                <label class="label">Kapasitas</label>
                <input type="number" name="kapasitas" value="{{ old('kapasitas', $kela->kapasitas) }}" class="input" required>
            </div>

            <div class="md:col-span-2">
                <label class="label">Keterangan</label>
                <textarea name="keterangan" rows="3" class="textarea">{{ old('keterangan', $kela->keterangan) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
            <a href="{{ route('kelas.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
