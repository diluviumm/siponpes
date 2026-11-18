@extends('layouts.app')

@section('title', $school ? "Kas Sekolah {$school->name}" : 'Kas Sekolah')

@section('page-title', $school ? "Kas Sekolah {$school->name}" : 'Kas Sekolah')

@section('content')

<x-school-filter :schools="$schools" route="kas.index" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Kas Sekolah']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kas Sekolah</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk melihat pemasukan &amp; pengeluaran kas.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk melihat kas sekolah.
    </div>

@else

    <div x-data="riwayatModal()">

    @php $isSuper = auth()->user()->isSuperadmin(); @endphp

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Kas Sekolah', 'url' => route('kas.index')] : 'Kas Sekolah',
        $school->name,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kas Sekolah</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — catat pemasukan & pengeluaran selain SPP.</p>
        </div>
        <div class="page-actions">
            <div class="flex gap-2">
                <x-export-actions :buttons="[
                    ['label' => 'Export PDF', 'url' => route('kas.index', array_merge(request()->query(), ['pdf' => 1]))],
                    ['label' => 'Export Excel', 'url' => route('kas.index', array_merge(request()->query(), ['excel' => 1]))],
                ]" />
                <a href="{{ route('kas.riwayat', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Seluruh Riwayat
                </a>
                <a href="{{ route('kas.create', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-primary btn-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Catat Transaksi
                </a>
            </div>
        </div>
    </div>

    <div class="card card-body mb-6 border-gold-300 bg-gold-50/50 text-sm text-ink/80">
        <p><strong>Perhatian:</strong> Kas sekolah mencatat uang milik sekolah sendiri (dari pendapatan SPP yang sudah dibayar, dana BOS/pemerintah, dan pemasukan lain). <strong>Terpisah dari tabungan santri.</strong> Tabungan santri tidak bisa dipakai untuk operasional sekolah.</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card card-body">
            <p class="text-xs font-medium uppercase tracking-wide text-ink/50">Saldo Kas Tersedia</p>
            <p class="text-2xl font-display font-semibold {{ $saldoKas < 0 ? 'text-rose-700' : 'text-pine-800' }} tabular-nums mt-1">Rp {{ number_format($saldoKas, 2, ',', '.') }}</p>
            <p class="text-xs text-ink/50 mt-1">Kumulatif s.d. {{ $to ? \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') : 'hari ini' }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-medium uppercase tracking-wide text-ink/50">Total Pemasukan</p>
            <p class="text-2xl font-display font-semibold text-pine-700 tabular-nums mt-1">Rp {{ number_format($totalPemasukan, 2, ',', '.') }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-medium uppercase tracking-wide text-ink/50">Total Pengeluaran</p>
            <p class="text-2xl font-display font-semibold text-rose-700 tabular-nums mt-1">Rp {{ number_format($totalPengeluaran, 2, ',', '.') }}</p>
        </div>
    </div>

    @if($saldoNegatif)
        <div class="card card-body mb-6 border-rose-300 bg-rose-50 text-sm text-rose-800">
            <p><strong>Perhatian:</strong> saldo kas saat ini negatif. Periksa transaksi lama atau tambahkan pemasukan kas terlebih dahulu. Pengeluaran baru akan ditolak sampai saldo mencukupi.</p>
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="card p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3">
        @if($isSuper)
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <select name="type" class="select">
            <option value="">Semua Jenis</option>
            <option value="pemasukan" {{ $type === 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
            <option value="pengeluaran" {{ $type === 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
        </select>
        <input type="date" name="from" value="{{ $from }}" class="input">
        <input type="date" name="to" value="{{ $to }}" class="input">
        <button type="submit" class="btn btn-primary btn-md w-full sm:w-auto">Filter</button>
    </form>

    {{-- Daftar --}}
    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-cream">
                    <tr>
                        <th class="th">Tanggal</th>
                        <th class="th">Jenis</th>
                        <th class="th">Akun</th>
                        <th class="th">Keterangan</th>
                        <th class="th text-right">Jumlah</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $kas)
                        <tr class="table-row">
                            <td class="td tabular-nums">{{ $kas->transaction_date->format('d/m/Y') }}</td>
                            <td class="td">
                                @if($kas->type === 'pemasukan')
                                    <x-badge tone="green">Pemasukan</x-badge>
                                @else
                                    <x-badge tone="red">Pengeluaran</x-badge>
                                @endif
                            </td>
                            <td class="td">{{ $kas->account?->account_code ?? '-' }} - {{ $kas->account?->account_name ?? '-' }}</td>
                            <td class="td">{{ $kas->description ?? '-' }}</td>
                            <td class="td text-right font-semibold tabular-nums {{ $kas->type === 'pengeluaran' ? 'text-rose-700' : 'text-pine-700' }}">
                                Rp {{ number_format($kas->amount, 2, ',', '.') }}
                            </td>
                            <td class="td text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('kas.edit', $kas) }}" class="btn btn-warning btn-sm">Edit</a>
                                    <button type="button" @@click="open({{ $kas->id }})" class="btn btn-ghost btn-sm">Riwayat</button>
                                    <form action="{{ route('kas.destroy', $kas) }}" method="POST"
                                          data-confirm="Hapus transaksi kas ini beserta jurnalnya?"
                                          data-confirm-label="Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="td text-center py-10 text-ink/50">Belum ada transaksi kas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>

    {{-- Modal Riwayat per transaksi --}}
    <div x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-pine-950/60" @@click="close()"></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="relative bg-paper w-full max-w-2xl rounded-xl shadow-xl border border-ink/10">
                <div class="flex items-center justify-between px-5 py-4 border-b border-ink/10">
                    <h3 class="font-display text-lg font-semibold text-pine-900">Riwayat Transaksi</h3>
                    <button type="button" @@click="close()" class="p-1.5 rounded-lg text-ink/50 hover:bg-cream hover:text-ink cursor-pointer" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4 max-h-[65vh] overflow-y-auto space-y-3">
                    <template x-for="log in logs" :key="log.id">
                        <div class="border border-ink/10 rounded-lg p-3">
                            <p class="font-medium">
                                <span x-text="label(log.action)"></span>
                                <span class="text-xs font-normal text-ink/50" x-text="'oleh ' + log.user + ' · ' + log.time"></span>
                            </p>
                            <p x-show="log.summary" class="text-sm text-ink/70 mt-1" x-text="log.summary"></p>
                            <ul x-show="log.changes.length" class="mt-1 space-y-0.5 text-xs">
                                <template x-for="ch in log.changes" :key="ch.field">
                                    <li><code class="text-ink/50" x-text="ch.field"></code>: <s class="text-rose-600" x-text="ch.old"></s> → <b class="text-pine-700" x-text="ch.new"></b></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    <p x-show="logs.length === 0" class="text-center text-ink/50 py-6">Belum ada riwayat.</p>
                </div>
            </div>
        </div>
    </div>

    @php
        $riwayatJson = $transactions->mapWithKeys(function ($kas) use ($auditLogs) {
            $logs = $auditLogs[$kas->id] ?? collect();

            return [$kas->id => $logs->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->name ?? '-',
                'time' => $log->created_at->format('d/m/Y H:i'),
                'summary' => $log->summary,
                'changes' => collect($log->changes ?? [])
                    ->reject(fn ($diff, $field) => in_array($field, ['updated_at', 'journal_id']))
                    ->map(fn ($diff, $field) => ['field' => $field, 'old' => $diff['old'], 'new' => $diff['new']])
                    ->values()
                    ->all(),
            ])->values()->all()];
        })->toJson();
    @endphp

    </div>

    <script>
        window.__kasRiwayat = {!! $riwayatJson !!};

        window.riwayatModal = function () {
            return {
                show: false,
                logs: [],
                labels: { created: 'Dibuat', updated: 'Diubah', deleted: 'Dihapus', restored: 'Dipulihkan' },
                open(id) {
                    this.logs = window.__kasRiwayat[id] || [];
                    this.show = true;
                },
                close() {
                    this.show = false;
                },
                label(action) {
                    return this.labels[action] || action;
                },
            };
        };
    </script>

@endif
@endsection
