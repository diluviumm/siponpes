@extends('layouts.pdf')

@section('content')
    @if($selectedAccount)
        <p class="strong">{{ $selectedAccount->account_code }} - {{ $selectedAccount->account_name }}</p>
        <p class="muted">Tipe akun: {{ ucfirst($selectedAccount->type) }}</p>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Referensi</th>
                    <th class="num">Debit</th>
                    <th class="num">Kredit</th>
                    <th class="num">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @php $running = $openingBalance ?? 0; @endphp
                @if(($openingBalance ?? 0) != 0)
                    <tr>
                        <td>—</td>
                        <td>Saldo Awal (sebelum periode)</td>
                        <td>—</td>
                        <td class="num">-</td>
                        <td class="num">-</td>
                        <td class="num">Rp {{ number_format($openingBalance, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @forelse($entries as $entry)
                    @php
                        if (in_array($selectedAccount->type, ['asset', 'expense'])) {
                            $running += $entry->debit - $entry->credit;
                        } else {
                            $running += $entry->credit - $entry->debit;
                        }
                    @endphp
                    <tr>
                        <td>{{ $entry->journal?->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $entry->journal?->description ?? '-' }}</td>
                        <td>{{ $entry->journal?->reference ?? '-' }}</td>
                        <td class="num">{{ $entry->debit ? 'Rp '.number_format($entry->debit, 0, ',', '.') : '-' }}</td>
                        <td class="num">{{ $entry->credit ? 'Rp '.number_format($entry->credit, 0, ',', '.') : '-' }}</td>
                        <td class="num">Rp {{ number_format($running, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada transaksi untuk akun ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="total">
                <tr>
                    <td colspan="5">Saldo Akun</td>
                    <td class="num">Rp {{ number_format($balance, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <p class="muted">Ringkasan seluruh akun pada periode terpilih (Buku Besar).</p>

        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Akun</th>
                    <th>Tipe</th>
                    <th class="num">Total Debit</th>
                    <th class="num">Total Kredit</th>
                    <th class="num">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accountsSummary as $row)
                    <tr>
                        <td>{{ $row['account']->account_code }}</td>
                        <td>{{ $row['account']->account_name }}</td>
                        <td>{{ ucfirst($row['account']->type) }}</td>
                        <td class="num">{{ $row['debit'] ? 'Rp '.number_format($row['debit'], 0, ',', '.') : '-' }}</td>
                        <td class="num">{{ $row['credit'] ? 'Rp '.number_format($row['credit'], 0, ',', '.') : '-' }}</td>
                        <td class="num">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
