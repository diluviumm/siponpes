@extends('layouts.app')

@section('title', 'Ubah Akun')

@section('page-title', 'Ubah Akun')

@section('content')
<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kode Akun', 'url' => route('accounts.index')], 'Ubah Akun']" />

<div class="max-w-2xl">
    <div class="card card-body">
        <h1 class="page-title mb-1">Ubah Akun</h1>
        <x-page-title-rule width="max-w-xs" />
        <p class="page-subtitle mb-6">{{ $account->account_code }} - {{ $account->account_name }}</p>

        <form action="{{ route('accounts.update', $account) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="label" for="account_code">Kode Akun</label>
                <input type="text" name="account_code" id="account_code" value="{{ old('account_code', $account->account_code) }}"
                       class="input" placeholder="contoh: 1010" maxlength="20" required>
                @error('account_code')
                    <p class="text-rose-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="account_name">Nama Akun</label>
                <input type="text" name="account_name" id="account_name" value="{{ old('account_name', $account->account_name) }}"
                       class="input" placeholder="contoh: Kas/Bank" maxlength="255" required>
                @error('account_name')
                    <p class="text-rose-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="type">Tipe Akun</label>
                <select name="type" id="type" class="select" required>
                    <option value="">— Pilih Tipe —</option>
                    @foreach([
                        'asset' => 'Aset',
                        'liability' => 'Kewajiban',
                        'equity' => 'Ekuitas',
                        'revenue' => 'Pendapatan',
                        'expense' => 'Beban',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $account->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="text-rose-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary btn-md">Simpan Perubahan</button>
                <a href="{{ route('accounts.index') }}" class="btn btn-ghost btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
