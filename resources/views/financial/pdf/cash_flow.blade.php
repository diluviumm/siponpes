@extends('layouts.pdf')

@section('content')
    <h2>Ringkasan Arus Kas</h2>
    <table class="balance">
        <tr>
            <td>Total Penerimaan Kas</td>
            <td class="num">Rp {{ number_format($totalInflow, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Pengeluaran Kas</td>
            <td class="num">Rp {{ number_format($totalOutflow, 0, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>Arus Kas Bersih</td>
            <td class="num">Rp {{ number_format($netCash, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Rincian Transaksi Kas</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Referensi</th>
                <th>Kategori</th>
                <th class="num">Penerimaan</th>
                <th class="num">Pengeluaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $detail)
                <tr>
                    <td>{{ $detail->date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $detail->description }}</td>
                    <td>{{ $detail->reference }}</td>
                    <td>{{ $detail->category }}</td>
                    <td class="num">{{ $detail->debit ? 'Rp '.number_format($detail->debit, 0, ',', '.') : '-' }}</td>
                    <td class="num">{{ $detail->credit ? 'Rp '.number_format($detail->credit, 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Belum ada transaksi kas.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="total">
            <tr>
                <td colspan="4">Total</td>
                <td class="num">Rp {{ number_format($totalInflow, 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($totalOutflow, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
