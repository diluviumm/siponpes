@extends('layouts.pdf')

@section('content')
    <h2>Ringkasan</h2>

    <table>
        <tr>
            <td>Saldo Kas (Periode)</td>
            <td class="num"><strong>Rp {{ number_format($saldoKas, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td>Total Pemasukan</td>
            <td class="num">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Pengeluaran</td>
            <td class="num">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Rincian Transaksi Kas</h2>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Akun</th>
                <th>Keterangan</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($exportTransactions as $kas)
                <tr>
                    <td>{{ $kas->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($kas->type) }}</td>
                    <td>{{ $kas->account?->account_code ?? '-' }} - {{ $kas->account?->account_name ?? '-' }}</td>
                    <td>{{ $kas->description ?? '-' }}</td>
                    <td class="num">Rp {{ number_format($kas->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Belum ada transaksi kas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
