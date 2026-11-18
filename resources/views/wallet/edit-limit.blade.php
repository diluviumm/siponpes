@extends('layouts.app')

@section('title', 'Perbarui Limit Harian')

@section('page-title', 'Perbarui Limit Harian')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : [])],
        ['label' => 'Tabungan Santri', 'url' => route('wallet.index', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : [])],
        'Limit ' . $santri->nama,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Perbarui Limit Harian</h1>
            <p class="page-subtitle">Atur batas pengeluaran harian tabungan santri.</p>
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

    <form action="{{ route('wallet.updateLimit', $santri) }}" method="POST" class="card card-body">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-cream p-4 rounded-lg border border-ink/10">
                <p class="kv-label">Nama Santri</p>
                <p class="kv-value">{{ $santri->nama }}</p>
            </div>

            <div class="bg-cream p-4 rounded-lg border border-ink/10">
                <p class="kv-label">Saldo Saat Ini</p>
                <p class="kv-value {{ ($santri->wallet?->balance ?? 0) > 0 ? 'text-pine-700' : 'text-ink/60' }}">
                    Rp {{ number_format($santri->wallet?->balance ?? 0, 2, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="mb-4">
            <label class="label">Limit Pengeluaran Harian (Rp)</label>
            <input type="text" inputmode="numeric" name="daily_spending_limit"
                value="{{ old('daily_spending_limit', $santri->wallet?->daily_spending_limit ?? 0) }}"
                class="input rupiah-input"
                required>
            <p class="text-sm text-ink/60 mt-1">Setel 0 untuk tanpa batas.</p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary btn-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan
            </button>
            <a href="{{ route('wallet.index', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>
@endsection
