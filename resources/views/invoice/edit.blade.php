@extends('layouts.app')

@section('page-title', 'Edit Invoice')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $invoice->santri->branch_id] : [])],
        ['label' => 'Invoice SPP', 'url' => route('invoices.index')],
        ['label' => $invoice->invoice_number, 'url' => route('invoices.show', $invoice)],
        'Edit',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Invoice</h1>
            <p class="page-subtitle">Perbarui detail invoice dan informasinya.</p>
        </div>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost btn-md">Kembali ke Detail</a>
    </div>

    @if($errors->any())
        <x-flash type="error">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-flash>
    @endif

    <div class="card card-body">
        <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="label">Santri</label>
                <select name="santri_id" class="select">
                    @foreach($santris as $santri)
                        <option value="{{ $santri->id }}" {{ old('santri_id', $invoice->santri_id) == $santri->id ? 'selected' : '' }}>
                            {{ $santri->nama }} ({{ $santri->nis }}) - {{ $santri->school?->name ?? '-' }} / {{ $santri->kelas?->nama_kelas ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Jatuh Tempo</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" class="input" required>
                </div>
                <div>
                    <label class="label">Jumlah (Rp)</label>
                    <input type="text" inputmode="numeric" name="amount" value="{{ old('amount', $invoice->total_amount) }}" class="input rupiah-input" required>
                </div>
            </div>

            <div>
                <label class="label">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description', $invoice->items->first()?->description ?? '') }}" class="input" required>
            </div>

            <div>
                <label class="label">Status</label>
                <select name="status" class="select" required>
                    <option value="unpaid" {{ old('status', $invoice->status) === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="expired" {{ old('status', $invoice->status) === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
                </select>
                <p class="text-sm text-ink/60 mt-1">Invoice yang sudah lunas tidak dapat diubah dan tidak bisa diubah langsung menjadi lunas dari sini — gunakan tombol "Bayar" di halaman detail.</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost btn-md">Batal</a>
                <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
