@extends('layouts.pdf')

@section('content')
    @foreach($rows->groupBy(fn ($row) => $canteenNames[$row->canteen_id] ?? 'Tanpa Kantin') as $canteenName => $group)
        <h2>{{ $canteenName }}</h2>

        <p class="strong">A. Pembayaran Saldo (Wallet)</p>
        <table>
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Satuan</th>
                    <th class="num">Jumlah</th>
                    <th class="num">Transaksi</th>
                    <th class="num">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @php $walletGroup = $group->where('payment_method', 'wallet'); @endphp
                @forelse($walletGroup as $row)
                    <tr>
                        <td>{{ $row->item_name }}</td>
                        <td>{{ $row->unit }}</td>
                        <td class="num">{{ (int) $row->total_qty }}</td>
                        <td class="num">{{ $row->tx_count }}</td>
                        <td class="num">Rp {{ number_format($row->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada pembayaran saldo.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="total">
                <tr>
                    <td colspan="4">Subtotal Saldo</td>
                    <td class="num">Rp {{ number_format($walletGroup->sum('total_revenue'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <p class="strong">B. Pembayaran Tunai (Cash)</p>
        <table>
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Satuan</th>
                    <th class="num">Jumlah</th>
                    <th class="num">Transaksi</th>
                    <th class="num">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @php $cashGroup = $group->where('payment_method', 'cash'); @endphp
                @forelse($cashGroup as $row)
                    <tr>
                        <td>{{ $row->item_name }}</td>
                        <td>{{ $row->unit }}</td>
                        <td class="num">{{ (int) $row->total_qty }}</td>
                        <td class="num">{{ $row->tx_count }}</td>
                        <td class="num">Rp {{ number_format($row->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada pembayaran tunai.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="total">
                <tr>
                    <td colspan="4">Subtotal Tunai</td>
                    <td class="num">Rp {{ number_format($cashGroup->sum('total_revenue'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="balance">
            <tr>
                <td>Subtotal {{ $canteenName }}</td>
                <td class="num">Rp {{ number_format($group->sum('total_revenue'), 0, ',', '.') }}</td>
            </tr>
        </table>
    @endforeach

    <table class="balance">
        <tr>
            <td>Total Saldo (Wallet)</td>
            <td class="num">Rp {{ number_format($totalWallet, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Tunai (Cash)</td>
            <td class="num">Rp {{ number_format($totalCash, 0, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>Total Keseluruhan</td>
            <td class="num">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
        </tr>
    </table>
@endsection
