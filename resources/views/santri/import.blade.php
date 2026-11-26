@extends('layouts.app')

@section('title', 'Import Data Santri dari Excel')

@section('page-title', 'Import Data Santri dari Excel')

@section('content')
<div class="max-w-4xl mx-auto">

    @php
    $schoolQuery = auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [];
@endphp

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $schoolQuery)],
        ['label' => 'Data Santri', 'url' => route('santri.index', $schoolQuery)],
        'Import Excel',
    ]" />

    @if($errors->any())
        <x-flash type="error">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-flash>
    @endif

    @if(session('importResult'))
        <div class="card overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-pine-700 to-pine-800 text-white px-6 py-4">
                <h2 class="text-lg font-bold">Import Selesai</h2>
                <p class="text-sm text-pine-100">Berikut ringkasan hasil import data Anda.</p>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                    <div class="bg-pine-50 rounded-lg p-4 text-center border border-pine-200">
                        <p class="text-3xl font-bold text-pine-700 tabular-nums">{{ session('importResult')['created_santri'] }}</p>
                        <p class="text-xs text-ink/70 mt-1">Santri Baru</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4 text-center border border-blue-200">
                        <p class="text-3xl font-bold text-blue-700 tabular-nums">{{ session('importResult')['existing_santri'] }}</p>
                        <p class="text-xs text-ink/70 mt-1">Santri Sudah Ada</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4 text-center border border-orange-200">
                        <p class="text-3xl font-bold text-orange-700 tabular-nums">{{ session('importResult')['kelas_diperbaiki'] }}</p>
                        <p class="text-xs text-ink/70 mt-1">Kelas Diperbaiki</p>
                    </div>
                    <div class="bg-cream rounded-lg p-4 text-center border border-ink/10">
                        <p class="text-3xl font-bold text-ink/80 tabular-nums">{{ session('importResult')['created_kelas'] }}</p>
                        <p class="text-xs text-ink/70 mt-1">Kelas Baru</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4 text-center border border-yellow-200 col-span-2 sm:col-span-3">
                        <p class="text-3xl font-bold text-yellow-700 tabular-nums">{{ session('importResult')['transactions_created'] }}</p>
                        <p class="text-xs text-ink/70 mt-1">Transaksi Tabungan</p>
                    </div>
                </div>

                <div class="bg-cream rounded-lg p-4 border border-ink/10 space-y-1">
                    <p class="text-sm text-ink/70">
                        Setoran masuk: <strong class="text-slate-900 tabular-nums">Rp {{ number_format(session('importResult')['total_amount'], 2, ',', '.') }}</strong>
                    </p>
                    <p class="text-sm text-ink/70">
                        Penarikan: <strong class="text-rose-700 tabular-nums">- Rp {{ number_format(session('importResult')['total_penarikan'], 2, ',', '.') }}</strong>
                    </p>
                    <p class="text-sm text-ink/70">
                        Saldo bersih ditambahkan: <strong class="text-pine-700 tabular-nums">Rp {{ number_format(session('importResult')['total_amount'] - session('importResult')['total_penarikan'], 2, ',', '.') }}</strong>
                    </p>
                </div>

                @if(count(session('importResult')['errors']) > 0)
                    <div class="mt-4 bg-rose-50 rounded-lg p-4 border border-rose-200">
                        <p class="font-semibold text-rose-700 mb-2">Catatan:</p>
                        <ul class="list-disc list-inside text-sm text-rose-700 space-y-1">
                            @foreach(session('importResult')['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('santri.index', $schoolQuery) }}" class="btn btn-primary btn-md">Lihat Data Santri</a>
                    <a href="{{ route('wallet.index', $schoolQuery) }}" class="btn btn-info btn-md">Lihat Tabungan Santri</a>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-body">
        <h2 class="text-xl font-bold mb-1">Upload File Excel</h2>
        <p class="text-sm text-ink/60 mb-6">
            Pilih file <strong>.xlsx</strong> untuk mengimport data santri dan tabungan ke
            <strong>{{ $school->name }}</strong>.
        </p>

        <form action="{{ route('santri.import.process', $schoolQuery) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="label">File Excel (.xlsx)</label>
                <input type="file" name="file" accept=".xlsx,.xls"
                    class="input py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-pine-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-pine-800 hover:file:bg-pine-100" required>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary btn-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </button>
                <a href="{{ route('santri.import.template', $schoolQuery) }}" class="btn btn-secondary btn-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Download Template
                </a>
                <a href="{{ route('santri.index', $schoolQuery) }}" class="btn btn-ghost btn-md">Kembali</a>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="font-bold text-blue-800 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Format File yang Didukung
        </h3>
        <p class="text-sm text-ink/80 mb-3">Aplikasi membaca struktur berikut. Kolom dideteksi otomatis dari baris judul (header). Sheet setoran (<code>S-*</code>) dan penarikan (<code>P-*</code>) dibaca untuk semua bulan Januari–Desember:</p>
        <div class="table-card">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-blue-200">
                        <th class="th">Sheet</th>
                        <th class="th">Kolom yang dibaca</th>
                        <th class="th">Dipindahkan ke</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-row">
                        <td class="td font-semibold">Siswa</td>
                        <td class="td">Nama, Kelas</td>
                        <td class="td">Data Santri</td>
                    </tr>
                    <tr class="table-row">
                        <td class="td font-semibold">S-Januari s.d. S-Desember</td>
                        <td class="td">Nama + jumlahkan semua kolom angka (per tanggal)</td>
                        <td class="td">Riwayat setoran tabungan tiap bulan (kredit)</td>
                    </tr>
                    <tr class="table-row">
                        <td class="td font-semibold">P-Januari s.d. P-Desember</td>
                        <td class="td">Nama + jumlahkan semua kolom angka (per tanggal)</td>
                        <td class="td">Riwayat penarikan tabungan tiap bulan (debit)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ul class="mt-4 text-sm text-ink/80 list-disc list-inside space-y-1">
            <li>Header dideteksi di baris 1–15. Kolom Nama dan Kelas wajib ada di sheet <strong>Siswa</strong>.</li>
            <li>Kelas yang belum ada otomatis dibuat.</li>
            <li>Santri yang sudah terdaftar (nama + kelas sama) tidak dibuat ulang, tetapi tabungannya tetap ditambahkan.</li>
            <li>Saldo wallet = total setoran semua bulan dikurangi total penarikan semua bulan.</li>
            <li>Import ulang file yang sama tidak akan menggandakan transaksi.</li>
        </ul>
    </div>

</div>
@endsection
