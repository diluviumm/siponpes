@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-title', $isSuperadmin ? 'Dashboard' : 'Dashboard Sekolah')

@section('content')

@if($school)
    <div class="card p-5 mb-6 bg-pine-50 border-pine-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-pine-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-gold-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-pine-700 font-semibold">{{ $isSuperadmin ? 'Sekolah Terpilih' : 'Sekolah Anda' }}</p>
                <p class="font-display text-xl font-semibold text-pine-900">{{ $school->name }}</p>
            </div>
        </div>
    </div>
@endif

@if(! $isSuperadmin && $school && ($totalKelas === 0 || $totalSantri === 0))
    <div class="card p-5 mb-6 border-pine-200 bg-pine-50/50">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-pine-700 text-gold-300 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <p class="font-display text-lg font-semibold text-pine-900">Mulai dari sini</p>
                <p class="text-sm text-ink/60">Langkah awal menyiapkan sekolah Anda di SIPONPES.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="{{ route('kelas.create') }}" class="flex items-center gap-3 rounded-lg border border-ink/10 bg-paper px-4 py-3 hover:border-pine-400 hover:shadow-sm transition-all">
                <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-bold {{ $totalKelas > 0 ? 'bg-pine-100 text-pine-700' : 'bg-gold-500 text-pine-950' }}">{{ $totalKelas > 0 ? '✓' : '1' }}</span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-ink">{{ $totalKelas > 0 ? 'Kelas sudah dibuat' : 'Buat Kelas' }}</span>
                    <span class="block text-xs text-ink/60">{{ $totalKelas > 0 ? 'Lihat &amp; kelola kelas' : 'Susun rombongan belajar' }}</span>
                </span>
            </a>
            <a href="{{ route('santri.import') }}" class="flex items-center gap-3 rounded-lg border border-ink/10 bg-paper px-4 py-3 hover:border-pine-400 hover:shadow-sm transition-all">
                <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-bold {{ $totalSantri > 0 ? 'bg-pine-100 text-pine-700' : 'bg-gold-500 text-pine-950' }}">{{ $totalSantri > 0 ? '✓' : '2' }}</span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-ink">{{ $totalSantri > 0 ? 'Santri sudah diinput' : 'Import Data Santri' }}</span>
                    <span class="block text-xs text-ink/60">{{ $totalSantri > 0 ? 'Lihat &amp; kelola santri' : 'Input massal dari Excel' }}</span>
                </span>
            </a>
            <a href="{{ route('spp_tariffs.create') }}" class="flex items-center gap-3 rounded-lg border border-ink/10 bg-paper px-4 py-3 hover:border-pine-400 hover:shadow-sm transition-all">
                <span class="w-8 h-8 rounded-full bg-gold-500 text-pine-950 flex items-center justify-center shrink-0 text-sm font-bold">3</span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-ink">Set Tarif SPP</span>
                    <span class="block text-xs text-ink/60">Nominal iuran per tahun ajaran</span>
                </span>
            </a>
            <a href="{{ route('invoices.create') }}" class="flex items-center gap-3 rounded-lg border border-ink/10 bg-paper px-4 py-3 hover:border-pine-400 hover:shadow-sm transition-all">
                <span class="w-8 h-8 rounded-full bg-gold-500 text-pine-950 flex items-center justify-center shrink-0 text-sm font-bold">4</span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-ink">Buat Invoice SPP</span>
                    <span class="block text-xs text-ink/60">Tagihan untuk santri</span>
                </span>
            </a>
        </div>
    </div>
@endif

@if($isSuperadmin)
    <form method="GET" action="{{ route('dashboard') }}" class="card p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="w-full sm:w-72">
                <label class="label">Sekolah</label>
                <select name="school_id" class="select" onchange="this.form.submit()">
                    <option value="">Semua Sekolah (Gabungan)</option>
                    @foreach($schools as $item)
                        <option value="{{ $item->id }}" {{ (string) $branchId === (string) $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-md">Tampilkan</button>
            @if($school)
                <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-md">Semua Sekolah</a>
            @endif
        </div>
    </form>
@endif

@if($isSuperadmin && ! $school && $pendingSchools->isNotEmpty())
    <div class="card p-5 mb-6 border-gold-300 bg-gold-50/50">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gold-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink">Ada {{ $pendingSchools->count() }} sekolah baru menunggu persetujuan</p>
                    <p class="text-xs text-ink/60">
                        @foreach($pendingSchools as $s)
                            <span class="inline-flex items-center gap-1 mr-3"><svg class="w-2 h-2 text-gold-500" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>{{ $s->name }}</span>
                        @endforeach
                    </p>
                </div>
            </div>
            <a href="{{ route('schools.index') }}" class="btn btn-primary btn-sm">Tinjau Sekarang</a>
        </div>
    </div>
@endif

<div class="page-header">
    <div>
        <h1 class="page-title">Ringkasan</h1>
        <x-page-title-rule width="max-w-xs" />
    </div>
</div>

@php
    $schoolQuery = $isSuperadmin && $school ? ['school_id' => $school->id] : [];
    $invoiceQuery = $isSuperadmin && $school ? ['branch_id' => $school->id] : [];
    $isCashier = auth()->user()->isCashier();
@endphp

@if($isCashier)
    <div class="card p-5 mb-6 bg-pine-50 border-pine-100">
        <p class="font-display text-lg font-semibold text-pine-900">Selamat datang di Kasir Kantin, {{ auth()->user()->name }}.</p>
        <p class="text-sm text-ink/60 mt-1">Gunakan menu <strong>Kasir Kantin</strong> untuk mencatat pembelian santri, atau <strong>Rekap Penjualan</strong> untuk melihat ringkasan penjualan.</p>
    </div>
@endif

@unless($isCashier)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

    @if($isSuperadmin && ! $school)
        <x-stat-card label="Total Sekolah" :value="$totalSchoolCount" sub="{{ $totalActiveSchoolCount }} aktif" tone="pine" href="{{ route('schools.index') }}">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    @else
        <x-stat-card label="Total Kelas" :value="$totalKelas" tone="pine" href="{{ route('kelas.index', $schoolQuery) }}">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    @endif

    <x-stat-card label="Total Santri" :value="$totalSantri" tone="blue" href="{{ route('santri.index', $schoolQuery) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
        </x-slot:icon>
    </x-stat-card>

    <x-stat-card label="Saldo Wallet"
                 value="Rp {{ number_format($totalWalletBalance, 2, ',', '.') }}"
                 valueClass="text-2xl"
                 sub="Total limit harian: Rp {{ number_format($totalDailyLimit, 2, ',', '.') }}"
                 tone="amber"
                 href="{{ route('wallet.index', $schoolQuery) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </x-slot:icon>
    </x-stat-card>

    <x-stat-card label="Total Invoice" :value="$totalInvoices" tone="indigo" href="{{ route('invoices.index', $invoiceQuery) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </x-slot:icon>
    </x-stat-card>

    <x-stat-card label="Belum Dibayar"
                 :value="$totalUnpaidInvoices"
                 sub="Outstanding: Rp {{ number_format($totalOutstandingAmount, 2, ',', '.') }}"
                 tone="rose"
                 href="{{ route('invoices.index', array_merge($invoiceQuery, ['status' => 'unpaid'])) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        </x-slot:icon>
    </x-stat-card>

    <x-stat-card label="Overdue" :value="$totalOverdueInvoices" tone="red" href="{{ route('invoices.index', array_merge($invoiceQuery, ['status' => 'overdue'])) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-stat-card>

    <x-stat-card label="Jatuh Tempo 7 Hari" :value="$totalUpcomingDueInvoices" sub="Perlu ditagih segera" tone="orange" href="{{ route('invoices.index', array_merge($invoiceQuery, ['status' => 'upcoming'])) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V4a1 1 0 10-2 0v1.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </x-slot:icon>
    </x-stat-card>

    <x-stat-card label="Pengingat 7 Hari" :value="$totalRemindersSentLast7Days" sub="Sudah dikirim" tone="teal" href="{{ route('invoices.index', $invoiceQuery) }}">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </x-slot:icon>
    </x-stat-card>

</div>
@endunless

<div class="card card-body mt-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900">Tren Pemasukan vs Pengeluaran</h2>
            <p class="text-sm text-ink/60">12 bulan terakhir {{ $school ? '— '.$school->name : '(semua sekolah)' }}</p>
        </div>
    </div>
    <div class="relative h-72">
        <canvas id="trendChart"></canvas>
    </div>
</div>

@if($isSuperadmin && ! $school)
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display text-lg font-semibold text-pine-900">Statistik per Sekolah</h2>
            <a href="{{ route('schools.index') }}" class="text-sm text-pine-700 hover:underline font-medium">Kelola Sekolah</a>
        </div>

        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="th">Sekolah</th>
                            <th class="th text-center">Santri</th>
                            <th class="th text-center">Kelas</th>
                            <th class="th text-right">Saldo Wallet</th>
                            <th class="th text-right">Limit</th>
                            <th class="th text-center">Invoice</th>
                            <th class="th text-center">Belum Bayar</th>
                            <th class="th text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                            <tr class="table-row">
                                <td class="td font-medium">{{ $school->name }}</td>
                                <td class="td text-center tabular-nums">{{ $school->santris_count }}</td>
                                <td class="td text-center tabular-nums">{{ $school->kelas_count }}</td>
                                <td class="td text-right tabular-nums">Rp {{ number_format($school->total_saldo, 2, ',', '.') }}</td>
                                <td class="td text-right tabular-nums">Rp {{ number_format($school->total_limit, 2, ',', '.') }}</td>
                                <td class="td text-center tabular-nums">{{ $school->total_invoices }}</td>
                                <td class="td text-center tabular-nums">{{ $school->unpaid_invoices }}</td>
                                <td class="td text-center whitespace-nowrap">
                                    <a href="{{ route('santri.index', ['school_id' => $school->id]) }}" class="btn btn-info btn-sm">Buka</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="td text-center py-10 text-ink/50">Belum ada sekolah aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('trendChart');
        if (!ctx || typeof Chart === 'undefined') {
            return;
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: @json($chartRevenue),
                        backgroundColor: 'rgba(70, 125, 84, 0.85)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Pengeluaran',
                        data: @json($chartExpense),
                        backgroundColor: 'rgba(185, 28, 28, 0.7)',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + ' jt';
                                }
                                return value.toLocaleString('id-ID');
                            },
                        },
                    },
                },
            },
        });
    });
</script>

@endsection
