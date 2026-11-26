@extends('layouts.app')

@section('title', 'Laporan Neraca')

@section('page-title', 'Laporan Neraca')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $schoolId ? ['school_id' => $schoolId] : [])], 'Laporan Neraca']" />
@include('financial._filter')
<div class="card card-body">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-pine-900">Laporan Neraca</h1>
            <p class="page-subtitle">Posisi aset, kewajiban, dan ekuitas pondok pesantren.</p>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-export-actions :buttons="[
                ['label' => 'Export PDF', 'url' => route('financial.balance_sheet', array_merge(request()->query(), ['pdf' => 1]))],
                ['label' => 'Export Excel', 'url' => route('financial.balance_sheet', array_merge(request()->query(), ['excel' => 1]))],
            ]" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-4 text-pine-700">Aset</h2>
            <div class="space-y-2">
                @forelse($assetsTotals as $item)
                    <div class="flex justify-between gap-4 py-1 {{ !$loop->last ? 'border-b border-ink/10' : '' }}">
                        <span>{{ $item->account->account_code }} - {{ $item->account->account_name }}</span>
                        <span class="font-medium tabular-nums whitespace-nowrap text-right">Rp {{ number_format($item->balance, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-ink/60 text-center py-4">Tidak ada data aset.</p>
                @endforelse
            </div>
            <div class="mt-4 pt-4 border-t-2 border-pine-300 font-semibold flex justify-between text-lg">
                <span>Total Aset</span>
                <span class="text-pine-700 tabular-nums whitespace-nowrap text-right">Rp {{ number_format($totalAssets, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-cream border border-ink/10 rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-4 text-blue-700">Kewajiban</h2>
                <div class="space-y-2">
                    @forelse($liabilitiesTotals as $item)
                        <div class="flex justify-between gap-4 py-1 {{ !$loop->last ? 'border-b border-ink/10' : '' }}">
                            <span>{{ $item->account->account_code }} - {{ $item->account->account_name }}</span>
                            <span class="font-medium tabular-nums whitespace-nowrap text-right">Rp {{ number_format($item->balance, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-ink/60 text-center py-4">Tidak ada data kewajiban.</p>
                    @endforelse
                </div>
                <div class="mt-4 pt-4 border-t-2 border-blue-300 font-semibold flex justify-between text-lg">
                    <span>Total Kewajiban</span>
                    <span class="text-blue-700 tabular-nums whitespace-nowrap text-right">Rp {{ number_format($totalLiabilities, 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-cream border border-ink/10 rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-4 text-purple-700">Ekuitas</h2>
                <div class="space-y-2">
                    @forelse($equityTotals as $item)
                        <div class="flex justify-between gap-4 py-1 {{ !$loop->last ? 'border-b border-ink/10' : '' }}">
                            <span>{{ $item->account->account_code }} - {{ $item->account->account_name }}</span>
                            <span class="font-medium tabular-nums whitespace-nowrap text-right">Rp {{ number_format($item->balance, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-ink/60 text-center py-4">Tidak ada akun ekuitas.</p>
                    @endforelse
                    <div class="flex justify-between gap-4 py-1 border-t border-ink/10">
                        <span>Saldo Laba Berjalan (Surplus/Defisit)</span>
                        <span class="font-medium tabular-nums whitespace-nowrap text-right">Rp {{ number_format($netIncome, 2, ',', '.') }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-purple-300 font-semibold flex justify-between text-lg">
                    <span>Total Ekuitas</span>
                    <span class="text-purple-700 tabular-nums whitespace-nowrap text-right">Rp {{ number_format($totalEquity, 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-pine-50 border-2 border-pine-200 rounded-lg p-4 font-semibold flex justify-between text-lg text-pine-800">
                <span>Total Kewajiban & Ekuitas</span>
                <span class="tabular-nums whitespace-nowrap text-right">Rp {{ number_format($totalLiabilities + $totalEquity, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
