@extends('layouts.app')

@section('title', 'Laporan Surplus/Defisit')

@section('page-title', 'Laporan Surplus/Defisit')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $schoolId ? ['school_id' => $schoolId] : [])], 'Laporan Surplus/Defisit']" />
@include('financial._filter')
<div class="card card-body">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h2 class="font-display text-xl font-semibold text-pine-900">Laporan Surplus/Defisit</h2>
            <p class="page-subtitle">Ringkasan pendapatan, beban, dan surplus/defisit pondok pesantren.</p>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-export-actions :buttons="[
                ['label' => 'Export PDF', 'url' => route('financial.income_statement', array_merge(request()->query(), ['pdf' => 1]))],
                ['label' => 'Export Excel', 'url' => route('financial.income_statement', array_merge(request()->query(), ['excel' => 1]))],
            ]" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-4 text-pine-700">Pendapatan</h3>
            <div class="space-y-2">
                @forelse($revenueTotals as $item)
                    <div class="flex justify-between py-1 {{ !$loop->last ? 'border-b border-ink/10' : '' }}">
                        <span>{{ $item->account->account_code }} - {{ $item->account->account_name }}</span>
                        <span class="font-medium tabular-nums">Rp {{ number_format($item->balance, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-ink/60 text-center py-4">Tidak ada data pendapatan.</p>
                @endforelse
            </div>
            <div class="mt-4 pt-4 border-t-2 border-pine-300 font-semibold flex justify-between text-lg">
                <span>Total Pendapatan</span>
                <span class="text-pine-700 tabular-nums">Rp {{ number_format($totalRevenue, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-cream border border-ink/10 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-4 text-rose-700">Beban</h3>
            <div class="space-y-2">
                @forelse($expenseTotals as $item)
                    <div class="flex justify-between py-1 {{ !$loop->last ? 'border-b border-ink/10' : '' }}">
                        <span>{{ $item->account->account_code }} - {{ $item->account->account_name }}</span>
                        <span class="font-medium tabular-nums">Rp {{ number_format($item->balance, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-ink/60 text-center py-4">Tidak ada data beban.</p>
                @endforelse
            </div>
            <div class="mt-4 pt-4 border-t-2 border-rose-300 font-semibold flex justify-between text-lg">
                <span>Total Beban</span>
                <span class="text-rose-700 tabular-nums">Rp {{ number_format($totalExpense, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-pine-50 border-2 border-pine-200 rounded-lg p-4 font-semibold flex justify-between text-xl text-pine-800">
        <span>Surplus (Defisit)</span>
        <span class="{{ $netIncome >= 0 ? 'text-pine-700' : 'text-rose-700' }} tabular-nums">
            Rp {{ number_format($netIncome, 2, ',', '.') }}
        </span>
    </div>
</div>
@endsection
