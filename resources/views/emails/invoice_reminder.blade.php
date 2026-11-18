<div style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h2>Pengingat Invoice</h2>
    <p>{{ $messageText }}</p>
    <p><strong>Nomor Invoice:</strong> {{ $invoice->invoice_number }}</p>
    <p><strong>Santri:</strong> {{ $invoice->santri->nama }} ({{ $invoice->santri->nis }})</p>
    <p><strong>Total:</strong> Rp {{ number_format($invoice->total_amount, 2, ',', '.') }}</p>
    <p><strong>Tanggal Jatuh Tempo:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
    <p>Terima kasih.</p>
</div>
