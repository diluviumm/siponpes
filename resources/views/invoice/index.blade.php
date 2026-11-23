@extends('layouts.app')

@section('title', 'Riwayat Invoice')

@section('page-title', 'Riwayat Invoice')

@section('content')

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', $branchId ? ['school_id' => $branchId] : [])], 'Riwayat Invoice']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Riwayat Invoice</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Kelola seluruh tagihan — SPP reguler dan tagihan tambahan.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Invoice
            </a>

            <x-export-actions :buttons="[
                ['label' => 'Export CSV', 'url' => route('invoices.export', request()->query())],
                ['label' => 'Export PDF', 'url' => route('invoices.export', array_merge(request()->query(), ['pdf' => 1]))],
            ]" />

            <div x-data="{ open: false }" class="relative">
                <button type="button" @@click="open = !open" class="btn btn-warning btn-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Kirim Pengingat
                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-cloak @@click.outside="open = false"
                     class="absolute right-0 mt-2 w-80 z-30 rounded-lg bg-paper border border-ink/10 shadow-lg p-2">
                    <form method="POST" action="{{ route('invoices.remind-overdue') }}"
                          data-confirm="Kirim pengingat ke {{ $overdueCount }} invoice yang sudah lewat jatuh tempo?"
                          data-confirm-label="Kirim">
                        @csrf
                        @if($branchId)
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif
                        <button type="submit"
                                class="w-full text-left text-sm px-3 py-2 rounded-md hover:bg-cream flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-pine-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            <span>
                                <span class="block font-medium text-ink">Kirim Pengingat Overdue</span>
                                <span class="block text-xs text-ink/60">Kirim ke {{ $overdueCount }} invoice yang sudah lewat jatuh tempo. Pengingat ke invoice yang sama dalam 24 jam terakhir dilewati otomatis.</span>
                            </span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('invoices.remind-all-unpaid') }}"
                          data-confirm="Kirim pengingat ke {{ $unpaidCount }} invoice yang belum dibayar?"
                          data-confirm-label="Kirim">
                        @csrf
                        @if($branchId)
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif
                        <button type="submit"
                                class="w-full text-left text-sm px-3 py-2 rounded-md hover:bg-cream flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-pine-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>
                                <span class="block font-medium text-ink">Kirim ke Semua Belum Dibayar</span>
                                <span class="block text-xs text-ink/60">Kirim ke {{ $unpaidCount }} invoice unpaid (termasuk yang belum jatuh tempo). Pengingat ke invoice yang sama dalam 24 jam terakhir dilewati otomatis.</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{-- Tab: SPP Reguler vs Tagihan Tambahan --}}
        <div class="flex items-center gap-1 mb-4 border-b border-ink/10">
            <a href="{{ route('invoices.index', array_merge(request()->query(), ['type' => 'spp', 'kelas_id' => null])) }}"
               class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 -mb-px transition-colors {{ $type === 'spp' ? 'border-pine-700 text-pine-900' : 'border-transparent text-ink/50 hover:text-ink/80' }}">
                SPP Reguler
            </a>
            <a href="{{ route('invoices.index', array_merge(request()->query(), ['type' => 'manual', 'kelas_id' => null])) }}"
               class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 -mb-px transition-colors {{ $type === 'manual' ? 'border-pine-700 text-pine-900' : 'border-transparent text-ink/50 hover:text-ink/80' }}">
                Tagihan Tambahan
            </a>
        </div>

        @if($type === 'spp')
            <div class="mb-4 text-xs text-ink/50 bg-ink/5 rounded-md px-3 py-2">
                Invoice SPP reguler berasal dari pembayaran lewat menu <strong>Pembayaran SPP</strong>. Pembayaran memotong saldo tabungan santri dan mencatatnya sebagai pendapatan sekolah. Tagihan tambahan (di luar SPP) bisa dibuat lewat tombol <strong>Tambah Invoice</strong>.
            </div>
        @endif

        {{-- Pill kelas --}}
        @if($kelasList->isNotEmpty())
            <div class="flex items-center gap-1.5 mb-4 flex-wrap">
                <a href="{{ route('invoices.index', array_merge(request()->query(), ['kelas_id' => null])) }}"
                   class="px-3 py-1 text-xs font-medium rounded-full border transition-colors {{ !$kelasId ? 'bg-pine-700 text-white border-pine-700' : 'bg-paper text-ink/60 border-ink/20 hover:border-pine-300 hover:text-pine-700' }}">
                    Semua Kelas
                </a>
                @foreach($kelasList as $kelas)
                    <a href="{{ route('invoices.index', array_merge(request()->query(), ['kelas_id' => $kelas->id])) }}"
                       class="px-3 py-1 text-xs font-medium rounded-full border transition-colors {{ $kelasId == $kelas->id ? 'bg-pine-700 text-white border-pine-700' : 'bg-paper text-ink/60 border-ink/20 hover:border-pine-300 hover:text-pine-700' }}">
                        {{ $kelas->nama_kelas }}
                        <span class="ml-1 text-[10px] opacity-75">({{ $kelas->santris_count }})</span>
                    </a>
                @endforeach
            </div>
        @endif

    <form method="GET" class="card p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 items-end">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Cari invoice..."
                   class="input pl-9 w-full min-w-0">
        </div>
        <select name="status" class="select">
            <option value="">Semua Status</option>
            <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
            <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Lunas</option>
            <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
            <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
            <option value="upcoming" {{ $status === 'upcoming' ? 'selected' : '' }}>Jatuh Tempo 7 Hari</option>
        </select>
        @if(auth()->user()->isSuperadmin())
            <select name="branch_id" class="select">
                <option value="">Semua Sekolah</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}" {{ $branchId == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                @endforeach
            </select>
        @endif
        <input type="date" name="from_date" value="{{ $fromDate }}" class="input" title="Jatuh tempo dari">
        <input type="date" name="to_date" value="{{ $toDate }}" class="input" title="Jatuh tempo sampai">
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary btn-md flex-1">Filter</button>
            @if($search || $status || $branchId || $kelasId || $fromDate || $toDate)
                <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-md flex-1">Reset</a>
            @endif
        </div>
    </form>

    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                <tr>
                    <th class="th">Invoice</th>
                    <th class="th">Santri</th>
                    <th class="th">Sekolah / Kelas</th>
                    <th class="th text-right">Jumlah</th>
                    <th class="th">Status</th>
                    <th class="th">Jatuh Tempo</th>
                    <th class="th text-center">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr class="table-row">
                        <td class="td">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-pine-700 hover:underline font-medium">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="td">{{ $invoice->santri->nama }}<br><span class="text-xs text-ink/50">{{ $invoice->santri->nis }}</span><br><span class="text-xs {{ ($invoice->santri->wallet?->balance ?? 0) >= $invoice->total_amount ? 'text-pine-700' : 'text-rose-600' }}">Saldo: Rp {{ number_format($invoice->santri->wallet?->balance ?? 0, 2, ',', '.') }}</span></td>
                        <td class="td">{{ $invoice->santri->school?->name ?? '-' }} / {{ $invoice->santri->kelas?->nama_kelas ?? '-' }}</td>
                        <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
                        <td class="td">
                            @if($invoice->status === 'paid')
                                <x-badge tone="green">Lunas</x-badge>
                            @elseif($invoice->status === 'expired')
                                <x-badge tone="red">Kedaluwarsa</x-badge>
                            @else
                                <x-badge tone="yellow">Belum Dibayar</x-badge>
                            @endif
                        </td>
                        <td class="td tabular-nums">{{ $invoice->due_date->format('d/m/Y') }}</td>
                        <td class="td text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost btn-sm">Detail</a>
                                @if($invoice->status === 'unpaid')
                                    @php
                                        $saldo = $invoice->santri->wallet?->balance ?? 0;
                                    @endphp
                                    @if($saldo >= $invoice->total_amount)
                                        <form action="{{ route('invoices.pay', $invoice) }}" method="POST"
                                              data-confirm="Bayar invoice {{ $invoice->invoice_number }} sebesar Rp {{ number_format($invoice->total_amount, 2, ',', '.') }} dari saldo tabungan santri (Rp {{ number_format($saldo, 2, ',', '.') }})?"
                                              data-confirm-label="Bayar">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">Bayar</button>
                                        </form>
                                    @else
                                        <button type="button" disabled
                                                title="Saldo {{ $invoice->santri->nama }} hanya Rp {{ number_format($saldo, 2, ',', '.') }} — tidak mencukupi. Isi saldo dahulu atau hubungi wali."
                                                class="btn btn-primary btn-sm opacity-50 cursor-not-allowed">Bayar</button>
                                    @endif
                                    <form action="{{ route('invoices.remind', $invoice) }}" method="POST"
                                          data-confirm="Kirim pengingat tagihan ini ke email/nomor wali?"
                                          data-confirm-label="Kirim">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">Kirim Pengingat</button>
                                    </form>
                                @endif
                                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                                      data-confirm="Yakin ingin menghapus invoice ini?"
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
                        <td colspan="7" class="td text-center py-10 text-ink/50">
                            Belum ada invoice.
                        @if($type === 'manual')
                                <br><a href="{{ route('invoices.create', $branchId ? (auth()->user()->isSuperadmin() ? ['school_id' => $branchId] : []) : []) }}" class="text-pine-700 hover:underline text-sm font-medium">Buat tagihan tambahan</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
