@extends('layouts.pdf')

@section('content')
    <h2>Daftar Invoice SPP</h2>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Santri</th>
                <th>NIS</th>
                <th>Sekolah</th>
                <th>Kelas</th>
                <th class="num">Total</th>
                <th>Status</th>
                <th>Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->santri->nama }}</td>
                    <td>{{ $invoice->santri->nis ?? '-' }}</td>
                    <td>{{ $invoice->santri->school?->name ?? '-' }}</td>
                    <td>{{ $invoice->santri->kelas?->nama_kelas ?? '-' }}</td>
                    <td class="num">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    <td>
                        @if($invoice->status === 'paid')
                            Lunas
                        @elseif($invoice->status === 'expired')
                            Kedaluwarsa
                        @else
                            Belum Dibayar
                        @endif
                    </td>
                    <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada invoice.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="total">
            <tr>
                <td colspan="5">Total</td>
                <td class="num">Rp {{ number_format($invoices->sum('total_amount'), 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
@endsection
