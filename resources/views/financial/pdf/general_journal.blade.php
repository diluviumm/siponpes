@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th style="width:10%">Tanggal</th>
                <th>Deskripsi</th>
                <th style="width:16%">Referensi</th>
                <th>Akun</th>
                <th class="num">Debit</th>
                <th class="num">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $journal)
                @foreach($journal->details as $detail)
                    <tr>
                        <td>{{ $journal->transaction_date->format('d/m/Y') }}</td>
                        <td>{{ $journal->description }}</td>
                        <td>{{ $journal->reference }}</td>
                        <td>{{ $detail->account?->account_code ?? '-' }} - {{ $detail->account?->account_name ?? 'Akun dihapus' }}</td>
                        <td class="num">{{ $detail->debit ? 'Rp '.number_format($detail->debit, 0, ',', '.') : '-' }}</td>
                        <td class="num">{{ $detail->credit ? 'Rp '.number_format($detail->credit, 0, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6">Belum ada data jurnal.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
