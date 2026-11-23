@extends('layouts.app')

@section('title', $school ? "Riwayat Kas {$school->name}" : 'Riwayat Kas')

@section('page-title', $school ? "Riwayat Kas {$school->name}" : 'Riwayat Kas')

@section('content')

<x-school-filter :schools="$schools" route="kas.riwayat" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Kas Sekolah', 'Seluruh Riwayat']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Seluruh Riwayat Kas</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk melihat seluruh riwayat transaksi kas, termasuk yang sudah dihapus.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk melihat riwayat kas.
    </div>

@else

    @php $isSuper = auth()->user()->isSuperadmin(); @endphp

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Kas Sekolah', 'url' => route('kas.index')] : 'Kas Sekolah',
        $isSuper ? ['label' => $school->name, 'url' => route('kas.index', ['school_id' => $school->id])] : $school->name,
        'Seluruh Riwayat',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Seluruh Riwayat Kas</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — riwayat pembuatan, perubahan, dan penghapusan transaksi kas.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('kas.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali ke Kas Sekolah</a>
        </div>
    </div>

    <div x-data="perubahanModal()">
        {{-- Pencarian --}}
        <form method="GET" class="mb-6">
            @if($isSuper)
                <input type="hidden" name="school_id" value="{{ $school->id }}">
            @endif
            <div class="flex gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[220px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari keterangan transaksi..."
                        class="input pl-10">
                </div>
                <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
                @if(request('search'))
                    <a href="{{ route('kas.riwayat', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
                @endif
            </div>
        </form>
        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-cream">
                        <tr>
                            <th class="th">Waktu</th>
                            <th class="th">Aksi</th>
                            <th class="th">Transaksi</th>
                            <th class="th">Keterangan</th>
                            <th class="th">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $kas = $kasById->get($log->auditable_id);
                                $deleted = $log->action === 'deleted' || ! $kas;
                            @endphp
                            <tr class="table-row align-top">
                                <td class="td tabular-nums whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="td">
                                    @if($log->action === 'created')
                                        <x-badge tone="green">Dibuat</x-badge>
                                    @elseif($log->action === 'updated')
                                        <x-badge tone="yellow">Diubah</x-badge>
                                    @elseif($log->action === 'restored')
                                        <x-badge tone="blue">Dipulihkan</x-badge>
                                    @else
                                        <x-badge tone="red">Dihapus</x-badge>
                                    @endif
                                </td>
                                <td class="td">
                                    @if($kas)
                                        <span class="inline-flex items-center gap-2">
                                            @if($kas->type === 'pemasukan')
                                                <x-badge tone="green">Pemasukan</x-badge>
                                            @else
                                                <x-badge tone="red">Pengeluaran</x-badge>
                                            @endif
                                            <span class="font-semibold tabular-nums {{ $kas->type === 'pengeluaran' ? 'text-rose-700' : 'text-pine-700' }}">
                                                Rp {{ number_format($kas->amount, 2, ',', '.') }}
                                            </span>
                                        </span>
                                        <span class="block text-xs text-ink/50 mt-1">
                                            {{ $kas->transaction_date->format('d/m/Y') }} · {{ $kas->account?->account_code ?? '-' }} - {{ $kas->account?->account_name ?? '-' }}
                                        </span>
                                    @elseif($deleted)
                                        <span class="text-ink/50">(Data transaksi sudah dihapus)</span>
                                    @endif
                                </td>
                                <td class="td">
                                    <p class="font-medium">{{ $log->summary ?: '-' }}</p>
                                    @if($log->changes && count($log->changes))
                                        <button type="button" @@click="open({{ $log->id }})"
                                                class="mt-1 text-xs text-pine-700 hover:underline cursor-pointer">Lihat Perubahan</button>
                                    @endif
                                </td>
                                <td class="td whitespace-nowrap">{{ $log->user?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="td text-center py-10 text-ink/50">Belum ada riwayat untuk sekolah ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>

        {{-- Modal Perubahan --}}
        <div x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-pine-950/60" @@click="close()"></div>
            <div class="relative min-h-full flex items-center justify-center p-4">
                <div class="relative bg-paper w-full max-w-2xl rounded-xl shadow-xl border border-ink/10">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-ink/10">
                        <h3 class="font-display text-lg font-semibold text-pine-900">Rincian Perubahan</h3>
                        <button type="button" @@click="close()" class="p-1.5 rounded-lg text-ink/50 hover:bg-cream hover:text-ink cursor-pointer" aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-5 py-4 max-h-[65vh] overflow-y-auto space-y-2">
                        <template x-for="ch in changes" :key="ch.field">
                            <div class="border border-ink/10 rounded-lg p-3 text-sm">
                                <p class="font-medium text-pine-900 mb-1" x-text="ch.field"></p>
                                <p class="text-ink/60">Sebelum: <s class="text-rose-600 break-all" x-text="ch.old"></s></p>
                                <p class="text-ink/60">Sesudah: <b class="text-pine-700 break-all" x-text="ch.new"></b></p>
                            </div>
                        </template>
                        <p x-show="changes.length === 0" class="text-center text-ink/50 py-6">Tidak ada rincian perubahan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $perubahanJson = $logs->mapWithKeys(fn ($log) => [
            $log->id => collect($log->changes ?? [])
                ->reject(fn ($diff, $field) => in_array($field, ['updated_at', 'journal_id']))
                ->map(fn ($diff, $field) => ['field' => $field, 'old' => $diff['old'], 'new' => $diff['new']])
                ->values()
                ->all(),
        ])->all();
    @endphp

    <script>
        window.__kasPerubahan = @json($perubahanJson);

        window.perubahanModal = function () {
            return {
                show: false,
                changes: [],
                open(id) {
                    this.changes = window.__kasPerubahan[id] || [];
                    this.show = true;
                },
                close() {
                    this.show = false;
                },
            };
        };
    </script>

@endif
@endsection
