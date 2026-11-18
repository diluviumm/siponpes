@extends('layouts.pdf')

@section('content')
    <h2>Aset</h2>
    <table>
        @forelse($assetsTotals as $item)
            <tr>
                <td>{{ $item->account->account_code }} - {{ $item->account->account_name }}</td>
                <td class="num">Rp {{ number_format($item->balance, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td>Tidak ada data aset.</td>
                <td class="num">-</td>
            </tr>
        @endforelse
        <tr class="total">
            <td>Total Aset</td>
            <td class="num">Rp {{ number_format($totalAssets, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Kewajiban</h2>
    <table>
        @forelse($liabilitiesTotals as $item)
            <tr>
                <td>{{ $item->account->account_code }} - {{ $item->account->account_name }}</td>
                <td class="num">Rp {{ number_format($item->balance, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td>Tidak ada data kewajiban.</td>
                <td class="num">-</td>
            </tr>
        @endforelse
        <tr class="total">
            <td>Total Kewajiban</td>
            <td class="num">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Ekuitas</h2>
    <table>
        @forelse($equityTotals as $item)
            <tr>
                <td>{{ $item->account->account_code }} - {{ $item->account->account_name }}</td>
                <td class="num">Rp {{ number_format($item->balance, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td>Tidak ada akun ekuitas.</td>
                <td class="num">-</td>
            </tr>
        @endforelse
        <tr>
            <td>Saldo Laba Berjalan (Surplus/Defisit)</td>
            <td class="num">Rp {{ number_format($netIncome, 0, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>Total Ekuitas</td>
            <td class="num">Rp {{ number_format($totalEquity, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="balance">
        <tr>
            <td>Total Kewajiban &amp; Ekuitas</td>
            <td class="num">Rp {{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</td>
        </tr>
    </table>
@endsection
