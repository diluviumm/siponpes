@extends('layouts.app')

@section('title', 'Catat Transaksi Kas')

@section('page-title', 'Catat Transaksi Kas')

@section('content')
@php $isSuper = auth()->user()->isSuperadmin(); @endphp

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
    $isSuper ? ['label' => 'Kas Sekolah', 'url' => route('kas.index')] : 'Kas Sekolah',
    $isSuper ? ['label' => $school->name, 'url' => route('kas.index', ['school_id' => $school->id])] : $school->name,
    'Catat Transaksi',
]" />

<div class="max-w-3xl mx-auto">
    <div class="page-header">
        <div>
            <h1 class="page-title">Catat Transaksi Kas</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — pemasukan & pengeluaran selain SPP (listrik, seragam, dll).</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('kas.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </div>

    <div class="card card-body mb-4 border-gold-300 bg-gold-50/50 text-sm text-ink/80">
        Saldo kas tersedia saat ini: <strong class="tabular-nums">Rp {{ number_format($saldoTersedia, 2, ',', '.') }}</strong>.
        Pengeluaran tidak boleh melebihi saldo tersedia.
    </div>

    <div class="card card-body">
        <form method="POST" action="{{ route('kas.store') }}" x-data="{ type: 'pemasukan', accountId: '{{ old('account_id') }}' }" class="space-y-5">
            @csrf

            @if($isSuper)
                <input type="hidden" name="branch_id" value="{{ $school->id }}">
            @endif

            <div>
                <span class="label">Jenis Transaksi</span>
                <div class="flex gap-3">
                    <button type="button" @@click="type = 'pemasukan'; accountId = ''"
                            class="flex-1 rounded-lg border-2 px-4 py-3 text-sm font-medium transition-colors"
                            :class="type === 'pemasukan' ? 'border-pine-600 bg-pine-50 text-pine-800' : 'border-ink/15 text-ink/60 hover:border-ink/30'">
                        Pemasukan
                    </button>
                    <button type="button" @@click="type = 'pengeluaran'; accountId = ''"
                            class="flex-1 rounded-lg border-2 px-4 py-3 text-sm font-medium transition-colors"
                            :class="type === 'pengeluaran' ? 'border-rose-600 bg-rose-50 text-rose-800' : 'border-ink/15 text-ink/60 hover:border-ink/30'">
                        Pengeluaran
                    </button>
                </div>
                <input type="hidden" name="type" :value="type">
                @error('type')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="transaction_date">Tanggal</label>
                <input id="transaction_date" type="date" name="transaction_date"
                       value="{{ old('transaction_date', now()->toDateString()) }}" class="input" required>
                @error('transaction_date')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="account_id">Akun</label>
                <select id="account_id" name="account_id" x-model="accountId" class="select" required>
                    <option value="">Pilih akun...</option>
                    <template x-if="type === 'pemasukan'">
                        <optgroup label="Akun Pemasukan">
                            @foreach($revenueAccounts as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </template>
                    <template x-if="type === 'pengeluaran'">
                        <optgroup label="Akun Pengeluaran">
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </template>
                </select>
                @error('account_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="amount">Jumlah (Rp)</label>
                <input id="amount" type="text" inputmode="numeric" name="amount"
                       value="{{ old('amount') }}" class="input rupiah-input" placeholder="100.000" required>
                @error('amount')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="description">Keterangan</label>
                <input id="description" type="text" name="description" value="{{ old('description') }}"
                       class="input" placeholder="Contoh: Pembayaran listrik bulan Agustus, Penjualan baju seragam...">
                @error('description')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="btn btn-primary btn-md flex-1 justify-center">Simpan Transaksi</button>
                <a href="{{ route('kas.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
