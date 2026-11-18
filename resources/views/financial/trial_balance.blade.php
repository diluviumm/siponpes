@extends('layouts.app')

@section('title', 'Neraca Saldo')

@section('page-title', 'Neraca Saldo')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $schoolId ? ['school_id' => $schoolId] : [])], 'Neraca Saldo']" />
@include('financial._filter')
<div class="card card-body">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h2 class="font-display text-xl font-semibold text-pine-900">Neraca Saldo</h2>
            <p class="page-subtitle">Ringkasan saldo debit dan kredit seluruh akun.</p>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-export-actions :buttons="[
                ['label' => 'Export PDF', 'url' => route('financial.trial_balance', array_merge(request()->query(), ['pdf' => 1]))],
                ['label' => 'Export Excel', 'url' => route('financial.trial_balance', array_merge(request()->query(), ['excel' => 1]))],
            ]" />
        </div>
    </div>

    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-cream">
                    <tr>
                        <th class="th">Akun</th>
                        <th class="th text-right">Debit</th>
                        <th class="th text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="table-row">
                            <td class="td">{{ $row['account']->account_code }} - {{ $row['account']->account_name }}</td>
                            <td class="td text-right tabular-nums {{ $row['debit'] ? 'font-medium' : '' }}">{{ $row['debit'] ? 'Rp ' . number_format($row['debit'], 2, ',', '.') : '-' }}</td>
                            <td class="td text-right tabular-nums {{ $row['credit'] ? 'font-medium' : '' }}">{{ $row['credit'] ? 'Rp ' . number_format($row['credit'], 2, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="td text-center py-10 text-ink/60">Belum ada data akun.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-cream font-semibold">
                    <tr>
                        <td class="td">Total</td>
                        <td class="td text-right tabular-nums">Rp {{ number_format($totalDebit, 2, ',', '.') }}</td>
                        <td class="td text-right tabular-nums">Rp {{ number_format($totalCredit, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
