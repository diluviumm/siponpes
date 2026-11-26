@extends('layouts.app')

@section('title', 'Jurnal Umum')

@section('page-title', 'Jurnal Umum')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $schoolId ? ['school_id' => $schoolId] : [])], 'Jurnal Umum']" />
@include('financial._filter')
<div class="card card-body">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-pine-900">Jurnal Umum</h1>
            <p class="page-subtitle">Daftar seluruh transaksi jurnal keuangan secara kronologis.</p>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-export-actions :buttons="[
                ['label' => 'Export PDF', 'url' => route('financial.journal', array_merge(request()->query(), ['pdf' => 1]))],
                ['label' => 'Export Excel', 'url' => route('financial.journal', array_merge(request()->query(), ['excel' => 1]))],
            ]" />
        </div>
    </div>

    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-cream">
                    <tr>
                        <th class="th">Tanggal</th>
                        <th class="th">Deskripsi</th>
                        <th class="th">Referensi</th>
                        <th class="th">Akun</th>
                        <th class="th text-right">Debit</th>
                        <th class="th text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($journals as $journal)
                        @foreach($journal->details as $detail)
                            <tr class="table-row">
                                <td class="td tabular-nums">{{ $journal->transaction_date->format('d/m/Y') }}</td>
                                <td class="td">{{ $journal->description }}</td>
                                <td class="td font-mono text-xs">{{ $journal->reference }}</td>
                                <td class="td">{{ $detail->account?->account_code ?? '-' }} - {{ $detail->account?->account_name ?? 'Akun dihapus' }}</td>
                                <td class="td text-right tabular-nums">{{ $detail->debit ? 'Rp ' . number_format($detail->debit, 2, ',', '.') : '-' }}</td>
                                <td class="td text-right tabular-nums">{{ $detail->credit ? 'Rp ' . number_format($detail->credit, 2, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="td text-center py-10 text-ink/60">Belum ada data jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
