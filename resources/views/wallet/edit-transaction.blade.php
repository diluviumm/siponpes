@extends('layouts.app')

@section('title', 'Top-up / Withdraw Wallet')

@section('page-title', 'Top-up / Withdraw Wallet')

@section('content')
<div class="max-w-3xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : [])],
        ['label' => 'Tabungan Santri', 'url' => route('wallet.index', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : [])],
        'Top-up / Tarik',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Top-up / Withdraw Wallet</h1>
            <p class="page-subtitle">Isi saldo atau tarik saldo tabungan santri.</p>
        </div>
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

    <form action="{{ route('wallet.processTransaction', $santri) }}" method="POST" class="card card-body">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-cream p-4 rounded-lg border border-ink/10">
                <p class="kv-label">Nama Santri</p>
                <p class="kv-value">{{ $santri->nama }}</p>
            </div>

            <div class="bg-cream p-4 rounded-lg border border-ink/10">
                <p class="kv-label">Saldo Saat Ini</p>
                <p class="kv-value {{ ($santri->wallet?->balance ?? 0) > 0 ? 'text-pine-700' : 'text-rose-600' }}">
                    Rp {{ number_format($santri->wallet?->balance ?? 0, 2, ',', '.') }}
                </p>
            </div>

            <div class="bg-cream p-4 rounded-lg border border-ink/10">
                <p class="kv-label">Limit Pengeluaran Harian</p>
                <p class="kv-value">
                    Rp {{ number_format($santri->wallet?->daily_spending_limit ?? 0, 2, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="mb-4">
            <label class="label">Jenis Transaksi</label>
            <select name="type" id="type" class="select" required>
                <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Top-up (Isi Saldo)</option>
                <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Withdraw (Tarik Saldo)</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="label">Jumlah (Rp)</label>
            <input type="text" inputmode="numeric" name="amount" id="amount"
                value="{{ old('amount') }}"
                class="input rupiah-input" required>
            <p id="amount-warning" class="text-sm text-rose-600 mt-1 hidden">
                Jumlah melebihi saldo yang tersedia!
            </p>
        </div>

        <div class="mb-4">
            <label class="label">Keterangan</label>
            <textarea name="description" rows="3" class="textarea">{{ old('description') }}</textarea>
        </div>

        <div class="flex gap-3">
            <button type="submit" id="submit-btn" class="btn btn-primary btn-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Proses
            </button>
            <a href="{{ route('wallet.index', auth()->user()->isSuperadmin() ? ['school_id' => $santri->branch_id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const typeSelect = document.getElementById('type');
    const amountInput = document.getElementById('amount');
    const warning = document.getElementById('amount-warning');
    const submitBtn = document.getElementById('submit-btn');
    const saldo = {{ $santri->wallet?->balance ?? 0 }};
    const dailyLimit = {{ $santri->wallet?->daily_spending_limit ?? 0 }};

    function validate() {
        const type = typeSelect.value;
        const amount = parseFloat(amountInput.value.replace(/\./g, '').replace(',', '.')) || 0;

        if (type === 'debit' && amount > saldo) {
            warning.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            warning.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        if (type === 'debit' && dailyLimit > 0 && amount > dailyLimit) {
            warning.textContent = 'Melebihi batas pengeluaran harian (Rp ' + new Intl.NumberFormat('id-ID').format(dailyLimit) + ')!';
            warning.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            warning.textContent = 'Jumlah melebihi saldo yang tersedia!';
        }
    }

    function applyTypeRules() {
        if (typeSelect.value === 'debit') {
            amountInput.max = saldo;
        } else {
            amountInput.removeAttribute('max');
        }
        validate();
    }

    typeSelect.addEventListener('change', applyTypeRules);
    amountInput.addEventListener('input', validate);
    applyTypeRules();
</script>
@endpush
@endsection
