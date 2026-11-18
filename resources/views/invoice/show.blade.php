@extends('layouts.app')

@section('page-title', 'Detail Invoice')

@section('content')
<div class="max-w-5xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $invoice->santri->branch_id] : [])],
        ['label' => 'Invoice SPP', 'url' => route('invoices.index')],
        $invoice->invoice_number,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Detail Invoice</h1>
            <p class="page-subtitle">Detail tagihan SPP dan status pembayaran.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-secondary btn-md">Cetak Bukti</a>
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-md">Kembali ke Daftar</a>
            @if($invoice->status !== 'paid')
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning btn-md">Edit Invoice</a>
            @endif
        </div>
    </div>

    <div class="card card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <h2 class="kv-label">Nomor Invoice</h2>
                <p class="kv-value">{{ $invoice->invoice_number }}</p>
            </div>
            <div>
                <h2 class="kv-label">Status</h2>
                <p class="mt-1">
                    @if($invoice->status === 'paid')
                        <x-badge tone="green">Lunas</x-badge>
                    @elseif($invoice->status === 'expired')
                        <x-badge tone="red">Kedaluwarsa</x-badge>
                    @else
                        <x-badge tone="yellow">Belum Dibayar</x-badge>
                    @endif
                </p>
            </div>
            <div>
                <h2 class="kv-label">Santri</h2>
                <p class="kv-value">{{ $invoice->santri->nama }} ({{ $invoice->santri->nis }})</p>
                <p class="text-sm text-ink/60">{{ $invoice->santri->school?->name ?? '-' }} / {{ $invoice->santri->kelas?->nama_kelas ?? '-' }}</p>
            </div>
            <div>
                <h2 class="kv-label">Jatuh Tempo</h2>
                <p class="kv-value">{{ $invoice->due_date->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-cream">
                        <tr>
                            <th class="th">Deskripsi</th>
                            <th class="th text-right">Jumlah</th>
                            <th class="th text-right">Harga Satuan</th>
                            <th class="th text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr class="table-row">
                                <td class="td">{{ $item->description }}</td>
                                <td class="td text-right tabular-nums">{{ $item->quantity }}</td>
                                <td class="td text-right tabular-nums">Rp {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="td text-right tabular-nums">Rp {{ number_format($item->total_amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-cream">
                        <tr>
                            <td colspan="3" class="td text-right font-semibold">Total</td>
                            <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <div class="bg-cream border border-ink/10 p-4 rounded-lg">
                <p class="kv-label">Saldo Wallet:</p>
                <p class="kv-value {{ ($invoice->santri->wallet?->balance ?? 0) > 0 ? 'text-pine-700' : 'text-ink/60' }}">
                    Rp {{ number_format($invoice->santri->wallet?->balance ?? 0, 2, ',', '.') }}
                </p>
            </div>

            @if($invoice->status === 'unpaid')
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <form action="{{ route('invoices.pay', $invoice) }}" method="POST" class="flex-shrink-0">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Bayar dengan Wallet
                        </button>
                    </form>
                    <form action="{{ route('invoices.remind', $invoice) }}" method="POST" class="flex-shrink-0">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-md">Kirim Pengingat</button>
                    </form>
                </div>
            @endif

            <div>
                <a href="{{ route('invoices.reminders', $invoice) }}" class="text-pine-700 hover:underline font-medium">Lihat Riwayat Pengingat</a>
            </div>

            @if($invoice->status === 'unpaid' || $invoice->status === 'expired')
                <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-3 border-t border-ink/10 pt-4">
                    @csrf
                    @method('PUT')
                    <label class="label sm:w-auto sm:mb-0 sm:mr-1">Ubah Status:</label>
                    <select name="status" class="select sm:w-auto">
                        <option value="unpaid" {{ $invoice->status === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                        <option value="expired" {{ $invoice->status === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
                    </select>
                    <button type="submit" class="btn btn-info btn-md">Update Status</button>
                </form>
            @else
                <div class="border-t border-ink/10 pt-4 text-sm text-ink/60">
                    Status <strong>Lunas</strong> tidak dapat diubah langsung — pembayaran hanya melalui tombol <strong>Bayar</strong> (memotong saldo tabungan santri). Invoice lunas juga tidak bisa diedit.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
