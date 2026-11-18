@extends('layouts.app')

@section('page-title', 'Riwayat Pengingat Invoice')

@section('content')
<div class="max-w-5xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $invoice->santri->branch_id] : [])],
        ['label' => 'Invoice SPP', 'url' => route('invoices.index')],
        ['label' => $invoice->invoice_number, 'url' => route('invoices.show', $invoice)],
        'Riwayat Pengingat',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Riwayat Pengingat Invoice</h1>
            <p class="page-subtitle">Daftar pengingat yang sudah dikirim untuk invoice ini.</p>
        </div>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost btn-md">Kembali ke Invoice</a>
    </div>

    <div class="card card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <h2 class="kv-label">Invoice</h2>
                <p class="kv-value">{{ $invoice->invoice_number }}</p>
            </div>
            <div>
                <h2 class="kv-label">Santri</h2>
                <p class="kv-value">{{ $invoice->santri->nama }} ({{ $invoice->santri->nis }})</p>
            </div>
        </div>

        @if($invoice->reminders->isEmpty())
            <div class="text-center py-12 text-ink/60">Belum ada pengingat yang dikirim untuk invoice ini.</div>
        @else
            <div class="table-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-cream">
                            <tr>
                                <th class="th">Tanggal Kirim</th>
                                <th class="th">Channel</th>
                                <th class="th">Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->reminders as $reminder)
                                <tr class="table-row">
                                    <td class="td tabular-nums">{{ $reminder->sent_at->format('d/m/Y H:i') }}</td>
                                    <td class="td">
                                        <x-badge tone="slate">{{ strtoupper($reminder->channel) }}</x-badge>
                                    </td>
                                    <td class="td whitespace-pre-line">{{ $reminder->message }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
