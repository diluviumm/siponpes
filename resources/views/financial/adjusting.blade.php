@extends('layouts.app')

@section('title', $school ? "Jurnal Penyesuaian {$school->name}" : 'Jurnal Penyesuaian')

@section('page-title', $school ? "Jurnal Penyesuaian {$school->name}" : 'Jurnal Penyesuaian')

@section('content')

@php $isSuper = auth()->user()->isSuperadmin(); @endphp

<x-school-filter :schools="$schools" route="financial.adjusting" :current="$school?->id" />

@if($isSuper && ! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Jurnal Penyesuaian']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Jurnal Penyesuaian &amp; Tutup Buku</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk mencatat jurnal penyesuaian.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk mencatat jurnal penyesuaian.
    </div>

@else

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Laporan Keuangan', 'url' => route('financial.journal')] : 'Laporan Keuangan',
        'Jurnal Penyesuaian & Tutup Buku',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Jurnal Penyesuaian &amp; Tutup Buku</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — catat jurnal manual dan tutup buku tahunan.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('financial.adjusting.store') }}"
          x-data="adjustingForm()" @@submit="return validate()" class="card card-body mb-6">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $school->id }}">

        <h3 class="font-display text-lg font-semibold text-pine-900 mb-4">Jurnal Penyesuaian</h3>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-lg px-4 py-3 mb-4 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="label">Tanggal</label>
                <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="input" required>
            </div>
            <div>
                <label class="label">Keterangan</label>
                <input type="text" name="description" value="{{ old('description') }}" class="input" placeholder="Mis. Penyesuaian stok / penyusutan" required maxlength="255">
            </div>
        </div>

        <div class="table-card mb-4">
            <table class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="th">Akun</th>
                        <th class="th text-right">Debit</th>
                        <th class="th text-right">Kredit</th>
                        <th class="th w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in rows" :key="idx">
                        <tr class="table-row">
                            <td class="td">
                                <select :name="'entries['+idx+'][account_id]'" x-model="row.account_id" class="select-sm text-sm" required>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="td">
                                <input type="number" :name="'entries['+idx+'][debit]'" x-model.number="row.debit" min="0" step="0.01" class="input-sm w-32 text-right">
                            </td>
                            <td class="td">
                                <input type="number" :name="'entries['+idx+'][credit]'" x-model.number="row.credit" min="0" step="0.01" class="input-sm w-32 text-right">
                            </td>
                            <td class="td text-center">
                                <button type="button" @@click="removeRow(idx)" class="text-rose-600 hover:text-rose-800 p-1" title="Hapus baris">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-cream font-semibold">
                    <tr>
                        <td class="td">Total</td>
                        <td class="td text-right tabular-nums" x-text="fmt(totalDebit)"></td>
                        <td class="td text-right tabular-nums" x-text="fmt(totalCredit)"></td>
                        <td class="td"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mb-4">
            <p class="text-sm" :class="balanced ? 'text-pine-700' : 'text-rose-700'" x-show="rows.length >= 2">
                <span x-text="balanced ? 'Seimbang (total debit = total kredit).' : 'Belum seimbang. Total debit harus sama dengan total kredit.'"></span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" @@click="addRow()" class="btn btn-secondary btn-md">+ Tambah Baris</button>
            <button type="submit" class="btn btn-primary btn-md">Simpan Jurnal</button>
        </div>
    </form>

    <div class="card card-body mb-6">
        <h3 class="font-display text-lg font-semibold text-pine-900 mb-2">Tutup Buku (Penutupan Tahun)</h3>
        <p class="text-sm text-ink/60 mb-4">
            Menutup seluruh pendapatan &amp; beban pada tahun tertentu ke akun <strong>Laba Ditahan</strong> (3010).
            Hanya bisa dilakukan sekali per tahun per sekolah.
        </p>
        <form method="POST" action="{{ route('financial.close') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end"
              data-confirm="Tutup buku tahun ini? Operasi ini memindahkan surplus/defisit ke Laba Ditahan dan tidak bisa diulang untuk tahun yang sama."
              data-confirm-label="Tutup Buku">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $school->id }}">
            <div>
                <label class="label">Tahun</label>
                <input type="number" name="year" value="{{ now()->year }}" min="2000" max="2100" class="input sm:w-40" required>
            </div>
            <button type="submit" class="btn btn-warning btn-md">Tutup Buku</button>
        </form>
    </div>

    @if($closings->isNotEmpty())
        <div class="card card-body">
            <h3 class="font-display text-lg font-semibold text-pine-900 mb-3">Riwayat Penutupan</h3>
            <div class="table-card">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="th">Referensi</th>
                            <th class="th">Deskripsi</th>
                            <th class="th">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($closings as $closing)
                            <tr class="table-row">
                                <td class="td font-mono text-xs">{{ $closing->reference }}</td>
                                <td class="td">{{ $closing->description }}</td>
                                <td class="td">{{ $closing->transaction_date->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endif

<script>
    window.adjustingForm = function () {
        return {
            rows: [
                { account_id: '', debit: '', credit: '' },
                { account_id: '', debit: '', credit: '' },
            ],
            get totalDebit() {
                return this.rows.reduce((sum, r) => sum + (Number(r.debit) || 0), 0);
            },
            get totalCredit() {
                return this.rows.reduce((sum, r) => sum + (Number(r.credit) || 0), 0);
            },
            get balanced() {
                return Math.abs(this.totalDebit - this.totalCredit) < 0.01;
            },
            fmt(n) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));
            },
            addRow() {
                this.rows.push({ account_id: '', debit: '', credit: '' });
            },
            removeRow(idx) {
                if (this.rows.length > 1) {
                    this.rows.splice(idx, 1);
                }
            },
            validate() {
                if (this.rows.some(r => !r.account_id)) {
                    window.__siponpesToast?.('error', 'Pilih akun untuk setiap baris.');
                    return false;
                }
                if (!this.balanced) {
                    window.__siponpesToast?.('error', 'Total debit harus sama dengan total kredit.');
                    return false;
                }
                return true;
            },
        };
    };
</script>

@endsection
