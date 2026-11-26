@extends('layouts.app')

@section('title', 'Laporan Arus Kas')

@section('page-title', 'Laporan Arus Kas')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $schoolId ? ['school_id' => $schoolId] : [])], 'Laporan Arus Kas']" />
@include('financial._filter')
<div class="card card-body">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-pine-900">Laporan Arus Kas</h1>
            <p class="page-subtitle">Penerimaan &amp; pengeluaran kas (akun Kas/Bank) pada periode terpilih.</p>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-export-actions :buttons="[
                ['label' => 'Export PDF', 'url' => route('financial.cash_flow', array_merge(request()->query(), ['pdf' => 1]))],
                ['label' => 'Export Excel', 'url' => route('financial.cash_flow', array_merge(request()->query(), ['excel' => 1]))],
            ]" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <p class="stat-card-label">Total Penerimaan Kas</p>
            <p class="text-2xl font-display font-semibold text-pine-700 tabular-nums">Rp {{ number_format($totalInflow, 2, ',', '.') }}</p>
        </div>
        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <p class="stat-card-label">Total Pengeluaran Kas</p>
            <p class="text-2xl font-display font-semibold text-rose-700 tabular-nums">Rp {{ number_format($totalOutflow, 2, ',', '.') }}</p>
        </div>
        <div class="bg-pine-50 border-2 border-pine-200 rounded-lg p-4">
            <p class="stat-card-label">Arus Kas Bersih</p>
            <p class="text-2xl font-display font-semibold text-pine-800 tabular-nums">Rp {{ number_format($netCash, 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 mb-6">
        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-3 text-pine-700">Penerimaan per Kategori</h2>
            @forelse($inflowByCategory as $category => $amount)
                <div class="flex justify-between py-1 border-b border-ink/10">
                    <span>{{ $category }}</span>
                    <span class="font-medium tabular-nums">Rp {{ number_format($amount, 2, ',', '.') }}</span>
                </div>
            @empty
                <p class="text-ink/60 text-center py-4">Tidak ada penerimaan.</p>
            @endforelse
        </div>
        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-3 text-rose-700">Pengeluaran per Kategori</h2>
            @forelse($outflowByCategory as $category => $amount)
                <div class="flex justify-between py-1 border-b border-ink/10">
                    <span>{{ $category }}</span>
                    <span class="font-medium tabular-nums">Rp {{ number_format($amount, 2, ',', '.') }}</span>
                </div>
            @empty
                <p class="text-ink/60 text-center py-4">Tidak ada pengeluaran.</p>
            @endforelse
        </div>
    </div>

    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="th">Tanggal</th>
                        <th class="th">Deskripsi</th>
                        <th class="th">Referensi</th>
                        <th class="th">Kategori</th>
                        <th class="th text-right">Penerimaan</th>
                        <th class="th text-right">Pengeluaran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($details as $detail)
                        <tr class="table-row">
                            <td class="td tabular-nums">{{ $detail->date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="td">{{ $detail->description }}</td>
                            <td class="td font-mono text-xs">{{ $detail->reference }}</td>
                            <td class="td"><x-badge tone="slate">{{ $detail->category }}</x-badge></td>
                            <td class="td text-right tabular-nums {{ $detail->debit ? 'text-pine-700 font-medium' : '' }}">{{ $detail->debit ? 'Rp '.number_format($detail->debit, 2, ',', '.') : '-' }}</td>
                            <td class="td text-right tabular-nums {{ $detail->credit ? 'text-rose-700 font-medium' : '' }}">{{ $detail->credit ? 'Rp '.number_format($detail->credit, 2, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="td text-center py-10 text-ink/60">Belum ada transaksi kas pada periode terpilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
