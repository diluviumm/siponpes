@extends('layouts.app')

@section('title', $school ? "Kelola Kantin {$school->name}" : 'Kelola Kantin')

@section('page-title', $school ? "Kelola Kantin {$school->name}" : 'Kelola Kantin')

@section('content')

@php $isSuper = auth()->user()->isSuperadmin(); @endphp

<x-school-filter :schools="$schools" route="kantin.manage" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Kelola Kantin']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Kantin &amp; Menu</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk mengelola kantin dan daftar menunya.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk mengelola kantin &amp; menu.
    </div>

@else

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        ['label' => 'Kasir Kantin', 'url' => route('kantin.index', $isSuper ? ['school_id' => $school->id] : [])],
        'Kelola Kantin & Menu',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Kantin &amp; Menu</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — atur kantin, daftar menu, status aktif, dan lihat riwayat belanjaan tiap kantin.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('kantin.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali ke Kasir</a>
        </div>
    </div>

    <form method="POST" action="{{ route('kantin.canteen.store') }}" class="card card-body mb-6">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $school->id }}">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <label class="label">Tambah Kantin Baru</label>
                <input type="text" name="name" class="input" placeholder="Mis. Kantin Utama, Kantin Sehat, Kantin Utara..." required maxlength="255">
            </div>
            <button type="submit" class="btn btn-primary btn-md shrink-0">Simpan Kantin</button>
        </div>
    </form>

    <form method="GET" class="mb-5">
        @if($isSuper)
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <div class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama kantin..." class="input pl-10">
            </div>
            <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
            @if($q)
                <a href="{{ route('kantin.manage', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
            @endif
        </div>
    </form>

    @forelse($canteens as $canteen)
        <div class="card card-body mb-4" x-data="{ open: false }">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 justify-between">
                <button type="button" @@click="open = !open"
                        class="flex items-center gap-3 min-w-0 text-left cursor-pointer group w-full lg:w-auto">
                    <div class="w-11 h-11 shrink-0 rounded-lg bg-pine-700/10 text-pine-700 flex items-center justify-center font-display font-semibold text-lg">
                        {{ mb_substr($canteen->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="font-display text-lg font-semibold text-pine-900 truncate flex items-center gap-2">
                            <span class="truncate">{{ $canteen->name }}</span>
                            @if(! $canteen->is_active)
                                <span class="badge badge-slate">Nonaktif</span>
                            @endif
                        </span>
                        <span class="block text-sm text-ink/60">
                            {{ $canteen->items_count }} menu · {{ $canteen->transactions_count }} transaksi terakhir
                        </span>
                    </div>
                    <svg class="w-5 h-5 shrink-0 text-pine-700 transition-transform duration-200"
                         :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('kantin.manage.show', $canteen) }}" class="btn btn-primary btn-sm">Kelola Menu</a>

                    <form action="{{ route('kantin.canteen.toggle', $canteen) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn {{ $canteen->is_active ? 'btn-warning' : 'btn-primary' }} btn-sm">
                            {{ $canteen->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>

                    <a href="{{ route('kantin.canteen.riwayat', $canteen) }}" class="btn btn-ghost btn-sm">Riwayat Hapus</a>

                    <form action="{{ route('kantin.canteen.destroy', $canteen) }}" method="POST"
                          data-confirm="Hapus kantin {{ $canteen->name }} beserta seluruh menunya? Riwayat transaksi tetap tersimpan. Anda bisa memulihkannya dari bagian Kantin Terhapus."
                          data-confirm-label="Hapus">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>

            {{-- Riwayat Belanjaan per kantin (terbuka saat kartu diklik) --}}
            <div x-show="open" x-cloak x-transition class="mt-4 border-t border-ink/10 pt-4">
                <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-ink/60">Riwayat Belanjaan</h3>
                    <a href="{{ route('kantin.rekap', $isSuper ? ['school_id' => $school->id] : []) }}" class="text-xs text-pine-700 hover:underline font-medium">Lihat Semua di Rekap &rarr;</a>
                </div>

                @if($canteen->transactions->isEmpty())
                    <p class="text-sm text-ink/50">Belum ada transaksi di kantin ini.</p>
                @else
                    <div class="table-card">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-cream">
                                    <tr>
                                        <th class="th">Tanggal</th>
                                        <th class="th">Item</th>
                                        <th class="th">Metode</th>
                                        <th class="th text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($canteen->transactions as $tx)
                                        <tr class="table-row">
                                            <td class="td tabular-nums whitespace-nowrap">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                                            <td class="td">
                                                @php
                                                    $names = $tx->items->pluck('item_name')->filter()->implode(', ');
                                                @endphp
                                                {{ $names ? \Illuminate\Support\Str::limit($names, 60) : 'Pembelian' }}
                                            </td>
                                            <td class="td">
                                                @if($tx->payment_method === 'cash')
                                                    <x-badge tone="yellow">Tunai</x-badge>
                                                @else
                                                    <x-badge tone="blue">Saldo</x-badge>
                                                @endif
                                            </td>
                                            <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($tx->total, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card card-body text-center text-ink/60">
            @if($q)
                Tidak ada kantin dengan nama "<strong>{{ $q }}</strong>".
            @else
                Belum ada kantin. Gunakan form di atas untuk membuat kantin pertama.
            @endif
        </div>
    @endforelse

    @if($deletedCanteens->isNotEmpty())
        <div class="card card-body mt-6">
            <h3 class="font-display text-lg font-semibold text-pine-900 mb-2">Kantin Terhapus</h3>
            <p class="text-sm text-ink/60 mb-4">Kantin yang dihapus bisa dipulihkan kembali (beserta menunya). Riwayat transaksi lama tetap tersimpan.</p>
            <div class="divide-y divide-ink/10">
                @foreach($deletedCanteens as $deleted)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between py-3">
                        <div class="min-w-0">
                            <p class="font-medium text-ink">{{ $deleted->name }}</p>
                            <p class="text-xs text-ink/50">Dihapus pada {{ optional($deleted->deleted_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        <form action="{{ route('kantin.canteen.restore', $deleted->id) }}" method="POST"
                              data-confirm="Pulihkan kantin {{ $deleted->name }} beserta menunya?"
                              data-confirm-label="Pulihkan">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Pulihkan</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

@endif
@endsection
