@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Akun</th>
                <th class="num">Debit</th>
                <th class="num">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['account']->account_code }} - {{ $row['account']->account_name }}</td>
                    <td class="num">{{ $row['debit'] ? 'Rp '.number_format($row['debit'], 0, ',', '.') : '-' }}</td>
                    <td class="num">{{ $row['credit'] ? 'Rp '.number_format($row['credit'], 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Belum ada data akun.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="total">
            <tr>
                <td>Total</td>
                <td class="num">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
