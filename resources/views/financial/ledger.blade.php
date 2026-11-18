@extends('layouts.app')

@section('title', 'Buku Besar')

@section('page-title', 'Buku Besar')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $schoolId ? ['school_id' => $schoolId] : [])], 'Buku Besar']" />
@include('financial._filter')
<div class="card card-body">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="font-display text-xl font-semibold text-pine-900">Buku Besar</h2>
            <p class="page-subtitle">Lihat mutasi per akun secara rinci.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <form method="GET" action="{{ route('financial.ledger') }}" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <select name="account_id" class="select sm:w-64">
                    <option value="">Pilih Akun</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ optional($selectedAccount)->id == $account->id ? 'selected' : '' }}>
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-md">Tampilkan</button>
            </form>
                <div class="flex gap-2 shrink-0">
                    <x-export-actions :buttons="[
                        ['label' => 'Export PDF', 'url' => route('financial.ledger', array_merge(request()->query(), ['pdf' => 1]))],
                        ['label' => 'Export Excel', 'url' => route('financial.ledger', array_merge(request()->query(), ['excel' => 1]))],
                    ]" />
                </div>
        </div>
    </div>

    @if($selectedAccount)
        <div class="mb-6 bg-pine-50 border border-pine-200 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-ink">{{ $selectedAccount->account_code }} - {{ $selectedAccount->account_name }}</h3>
            <p class="text-sm text-ink/70">Tipe akun: <span class="font-medium capitalize">{{ $selectedAccount->type }}</span></p>
        </div>

        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-cream">
                        <tr>
                            <th class="th">Tanggal</th>
                            <th class="th">Deskripsi</th>
                            <th class="th">Referensi</th>
                            <th class="th text-right">Debit</th>
                            <th class="th text-right">Kredit</th>
                            <th class="th text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $running = $openingBalance ?? 0; @endphp
                        @if(($openingBalance ?? 0) != 0)
                            <tr class="table-row">
                                <td class="td">—</td>
                                <td class="td font-medium text-ink/70">Saldo Awal (sebelum periode)</td>
                                <td class="td font-mono text-xs">—</td>
                                <td class="td"></td>
                                <td class="td"></td>
                                <td class="td text-right font-medium tabular-nums">Rp {{ number_format($openingBalance, 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        @forelse($entries as $entry)
                            @php
                                if (in_array($selectedAccount->type, ['asset', 'expense'])) {
                                    $running += $entry->debit - $entry->credit;
                                } else {
                                    $running += $entry->credit - $entry->debit;
                                }
                            @endphp
                            <tr class="table-row">
                                <td class="td tabular-nums">{{ $entry->journal?->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="td">{{ $entry->journal?->description ?? '-' }}</td>
                                <td class="td font-mono text-xs">{{ $entry->journal?->reference ?? '-' }}</td>
                                <td class="td text-right tabular-nums">{{ $entry->debit ? 'Rp ' . number_format($entry->debit, 2, ',', '.') : '-' }}</td>
                                <td class="td text-right tabular-nums">{{ $entry->credit ? 'Rp ' . number_format($entry->credit, 2, ',', '.') : '-' }}</td>
                                <td class="td text-right font-medium tabular-nums">Rp {{ number_format($running, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="td text-center py-10 text-ink/60">Belum ada transaksi untuk akun ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 text-right text-lg font-semibold bg-pine-50 border border-pine-200 rounded-lg p-4 text-pine-800">
            Saldo Akun: Rp {{ number_format($balance, 2, ',', '.') }}
        </div>
    @else
        <div class="text-center py-12 text-ink/60 bg-cream rounded-xl border border-ink/10">
            Pilih akun untuk melihat detail buku besar.
        </div>
    @endif
</div>
@endsection
