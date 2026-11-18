@extends('layouts.pdf')

@section('content')
    <h2>Detail Tagihan SPP & Status Pembayaran</h2>

    <p class="strong">Nomor Invoice: {{ $invoice->invoice_number }}</p>
    <p>
        Status Pembayaran:
        <span class="strong">
            @if($invoice->status === 'paid')
                LUNAS
            @elseif($invoice->status === 'expired')
                KEDALUWARSA
            @else
                BELUM DIBAYAR
            @endif
        </span>
    </p>

    <table>
        <tr>
            <td><strong>Nama Santri</strong></td>
            <td>{{ $invoice->santri->nama }}</td>
            <td><strong>NIS</strong></td>
            <td>{{ $invoice->santri->nis ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Sekolah</strong></td>
            <td>{{ $invoice->santri->school?->name ?? '-' }}</td>
            <td><strong>Kelas</strong></td>
            <td>{{ $invoice->santri->kelas?->nama_kelas ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Jatuh Tempo</strong></td>
            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
            <td><strong>Dibuat</strong></td>
            <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
        </tr>
    </table>

    <h2>Rincian Tagihan</h2>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="num">Jumlah</th>
                <th class="num">Harga Satuan</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Tidak ada rincian.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="total">
            <tr>
                <td colspan="3">Total Tagihan</td>
                <td class="num">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <p>Saldo Wallet Santri: <strong>Rp {{ number_format($invoice->santri->wallet?->balance ?? 0, 0, ',', '.') }}</strong></p>

    <div class="signature-block">
        <table>
            <tr>
                <td style="border:none; width:50%; text-align:center;">
                    <p>Diketahui oleh,</p>
                    <br><br><br>
                    <p>(...........................)</p>
                </td>
                <td style="border:none; width:50%; text-align:center;">
                    <p>Hormat Kami,</p>
                    <br><br><br>
                    <p>(...........................)</p>
                </td>
            </tr>
        </table>
    </div>
@endsection
