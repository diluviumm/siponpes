@extends('layouts.pdf')

@section('content')
    <h2>Pendapatan</h2>
    <table>
        @forelse($revenueTotals as $item)
            <tr>
                <td>{{ $item->account->account_code }} - {{ $item->account->account_name }}</td>
                <td class="num">Rp {{ number_format($item->balance, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td>Tidak ada data pendapatan.</td>
                <td class="num">-</td>
            </tr>
        @endforelse
        <tr class="total">
            <td>Total Pendapatan</td>
            <td class="num">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Beban</h2>
    <table>
        @forelse($expenseTotals as $item)
            <tr>
                <td>{{ $item->account->account_code }} - {{ $item->account->account_name }}</td>
                <td class="num">Rp {{ number_format($item->balance, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td>Tidak ada data beban.</td>
                <td class="num">-</td>
            </tr>
        @endforelse
        <tr class="total">
            <td>Total Beban</td>
            <td class="num">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="balance">
        <tr>
            <td>Surplus (Defisit)</td>
            <td class="num">Rp {{ number_format($netIncome, 0, ',', '.') }}</td>
        </tr>
    </table>
@endsection
