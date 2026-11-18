@extends('layouts.app')

@section('title', $school ? "Rekap Penjualan {$school->name}" : 'Rekap Penjualan')

@section('page-title', $school ? "Rekap Penjualan {$school->name}" : 'Rekap Penjualan')

@section('content')

<x-school-filter :schools="$schools" route="kantin.rekap" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Rekap Penjualan']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Rekap Penjualan Kantin</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk melihat rekap penjualan kantin.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk melihat rekap penjualan.
    </div>

@else

    @php $isSuper = auth()->user()->isSuperadmin(); @endphp

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        ['label' => 'Kasir Kantin', 'url' => route('kantin.index', $isSuper ? ['school_id' => $school->id] : [])],
        'Rekap Penjualan',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Rekap Penjualan Kantin</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — ringkasan penjualan per kantin dan per menu.</p>
        </div>
        <div class="page-actions flex gap-2">
            <x-export-actions :buttons="[
                ['label' => 'Export PDF', 'url' => route('kantin.rekap', array_merge($isSuper ? ['school_id' => $school->id] : [], array_filter(['from' => $from, 'to' => $to]), ['pdf' => 1]))],
                ['label' => 'Export Excel', 'url' => route('kantin.rekap', array_merge($isSuper ? ['school_id' => $school->id] : [], array_filter(['from' => $from, 'to' => $to]), ['excel' => 1]))],
            ]" />
            <a href="{{ route('kantin.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali ke Kasir</a>
        </div>
    </div>

    <form method="GET" class="card p-4 mb-6 flex flex-col sm:flex-row gap-3 sm:items-end">
        @if($isSuper)
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <div>
            <label class="label">Dari Tanggal</label>
            <input type="date" name="from" class="input" value="{{ $from }}">
        </div>
        <div>
            <label class="label">Sampai Tanggal</label>
            <input type="date" name="to" class="input" value="{{ $to }}">
        </div>
        <button type="submit" class="btn btn-primary btn-md">Tampilkan</button>
        @if($from || $to)
            <a href="{{ route('kantin.rekap', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">Reset</a>
        @endif
    </form>

    @if($rows->isEmpty())
        <div class="card card-body text-center text-ink/60">
            Belum ada data penjualan pada periode terpilih.
        </div>
    @else
        @foreach($rows->groupBy(fn ($row) => $canteenNames[$row->canteen_id] ?? 'Tanpa Kantin') as $canteenName => $group)
            @php
                $walletGroup = $group->where('payment_method', 'wallet');
                $cashGroup = $group->where('payment_method', 'cash');
            @endphp
            <div class="card card-body mb-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <h2 class="font-display text-lg font-semibold text-pine-900">{{ $canteenName }}</h2>
                    <span class="text-sm font-semibold text-pine-700 tabular-nums">Rp {{ number_format($group->sum('total_revenue'), 2, ',', '.') }}</span>
                </div>

                <div class="table-card">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="th">Menu</th>
                                <th class="th">Metode</th>
                                <th class="th">Satuan</th>
                                <th class="th text-right">Jumlah Terjual</th>
                                <th class="th text-right">Transaksi</th>
                                <th class="th text-right">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($walletGroup as $row)
                                <tr class="table-row">
                                    <td class="td font-medium">{{ $row->item_name }}</td>
                                    <td class="td"><x-badge tone="blue">Saldo (Wallet)</x-badge></td>
                                    <td class="td">{{ $row->unit }}</td>
                                    <td class="td text-right tabular-nums">{{ (int) $row->total_qty }}</td>
                                    <td class="td text-right tabular-nums">{{ $row->tx_count }}</td>
                                    <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($row->total_revenue, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="td text-center py-6 text-ink/50">Tidak ada pembayaran saldo.</td>
                                </tr>
                            @endforelse
                            @forelse($cashGroup as $row)
                                <tr class="table-row">
                                    <td class="td font-medium">{{ $row->item_name }}</td>
                                    <td class="td"><x-badge tone="yellow">Tunai (Cash)</x-badge></td>
                                    <td class="td">{{ $row->unit }}</td>
                                    <td class="td text-right tabular-nums">{{ (int) $row->total_qty }}</td>
                                    <td class="td text-right tabular-nums">{{ $row->tx_count }}</td>
                                    <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($row->total_revenue, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="td text-center py-6 text-ink/50">Tidak ada pembayaran tunai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-cream">
                            <tr>
                                <td colspan="3" class="td text-right font-semibold">Subtotal {{ $canteenName }}</td>
                                <td class="td text-right tabular-nums">{{ (int) $group->sum('total_qty') }}</td>
                                <td class="td text-right tabular-nums">{{ $group->sum('tx_count') }}</td>
                                <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($group->sum('total_revenue'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="stat-card-label">Total Saldo (Wallet)</p>
                <p class="text-xl font-display font-semibold text-blue-700 tabular-nums">Rp {{ number_format($totalWallet, 2, ',', '.') }}</p>
            </div>
            <div class="bg-gold-50 border border-gold-300 rounded-lg p-4">
                <p class="stat-card-label">Total Tunai (Cash)</p>
                <p class="text-xl font-display font-semibold text-gold-700 tabular-nums">Rp {{ number_format($totalCash, 2, ',', '.') }}</p>
            </div>
            <div class="bg-pine-50 border-2 border-pine-200 rounded-lg p-4">
                <p class="stat-card-label">Total Keseluruhan</p>
                <p class="text-xl font-display font-semibold text-pine-800 tabular-nums">Rp {{ number_format($totalRevenue, 2, ',', '.') }}</p>
            </div>
        </div>
    @endif

@endif
@endsection
