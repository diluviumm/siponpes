@extends('layouts.app')

@section('title', 'Riwayat Transaksi Wallet')

@section('page-title', 'Riwayat Transaksi')

@section('content')

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : [])],
        ['label' => 'Tabungan Santri', 'url' => route('wallet.index', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : [])],
        'Riwayat ' . $santri->nama,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Riwayat Transaksi Wallet</h1>
            <p class="page-subtitle">Riwayat top-up dan penarikan saldo santri.</p>
        </div>
        <a href="{{ route('wallet.index') }}" class="btn btn-ghost btn-md">Kembali</a>
    </div>

    <div class="card overflow-hidden">
        <div class="p-6 border-b border-ink/10 bg-cream grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="kv-label">Santri</p>
                <p class="kv-value">{{ $santri->nama }}</p>
            </div>
            <div>
                <p class="kv-label">Saldo Saat Ini</p>
                <p class="kv-value {{ ($santri->wallet?->balance ?? 0) > 0 ? 'text-pine-700' : 'text-rose-600' }}">
                    Rp {{ number_format($santri->wallet?->balance ?? 0, 2, ',', '.') }}
                </p>
            </div>
            <div>
                <p class="kv-label">Limit Harian</p>
                <p class="kv-value">Rp {{ number_format($santri->wallet?->daily_spending_limit ?? 0, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-cream">
                    <tr>
                        <th class="th">Tanggal</th>
                        <th class="th">Tipe</th>
                        <th class="th text-right">Jumlah</th>
                        <th class="th">Keterangan</th>
                        <th class="th">Referensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr class="table-row">
                            <td class="td">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            <td class="td">
                                @if($transaction->type === 'credit')
                                    <x-badge tone="green">Top-up</x-badge>
                                @else
                                    <x-badge tone="red">Withdraw</x-badge>
                                @endif
                            </td>
                            <td class="td text-right {{ $transaction->type === 'credit' ? 'text-pine-700' : 'text-rose-600' }} font-semibold tabular-nums">
                                {{ $transaction->type === 'credit' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 2, ',', '.') }}
                            </td>
                            <td class="td">{{ $transaction->description ?? '-' }}</td>
                            <td class="td font-mono">{{ $transaction->reference_id ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="td text-center py-10 text-ink/60">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="p-4 border-t border-ink/10">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
@endsection
