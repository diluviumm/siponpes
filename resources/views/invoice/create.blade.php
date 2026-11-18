@extends('layouts.app')

@section('page-title', 'Buat Invoice Baru')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Tagihan Tambahan', 'url' => route('invoices.index')], 'Buat Tagihan']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Buat Invoice Baru</h1>
            <p class="page-subtitle">Buat tagihan di luar SPP reguler — misal biaya pendaftaran, denda, atau kegiatan.</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-md">Kembali ke Daftar</a>
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

    <div class="card card-body">
        <form action="{{ route('invoices.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="label">Santri</label>
                <select name="santri_id" class="select">
                    <option value="">Pilih Santri</option>
                    @foreach($santris as $santri)
                        <option value="{{ $santri->id }}" {{ old('santri_id') == $santri->id ? 'selected' : '' }}>
                            {{ $santri->nama }} ({{ $santri->nis }}) - {{ $santri->school?->name ?? '-' }} / {{ $santri->kelas?->nama_kelas ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Jatuh Tempo</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="input" required>
                </div>
                <div>
                    <label class="label">Jumlah (Rp)</label>
                    <input type="text" inputmode="numeric" name="amount" value="{{ old('amount') }}" class="input rupiah-input" required>
                </div>
            </div>

            <div>
                <label class="label">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description') }}" class="input" required>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-md">Batal</a>
                <button type="submit" class="btn btn-primary btn-md">Simpan Invoice</button>
            </div>
        </form>
    </div>
</div>
@endsection
