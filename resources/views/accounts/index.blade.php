@extends('layouts.app')

@section('title', 'Kode Akun')

@section('page-title', 'Kode Akun')

@section('content')

@php
    $typeLabels = [
        'asset' => 'Aset',
        'liability' => 'Kewajiban',
        'equity' => 'Ekuitas',
        'revenue' => 'Pendapatan',
        'expense' => 'Beban',
    ];
    $typeColors = [
        'asset' => 'badge-blue',
        'liability' => 'badge-yellow',
        'equity' => 'badge-slate',
        'revenue' => 'badge-green',
        'expense' => 'badge-red',
    ];
@endphp

<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Kode Akun']" />

<div class="page-header">
    <div>
        <h1 class="page-title">Chart of Accounts</h1>
        <x-page-title-rule width="max-w-xs" />
        <p class="page-subtitle">Kelola kode akun untuk jurnal dan laporan keuangan.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Akun
        </a>
    </div>
</div>

<div class="table-card">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="th">Kode</th>
                    <th class="th">Nama Akun</th>
                    <th class="th">Tipe</th>
                    <th class="th text-center">Dipakai Jurnal</th>
                    <th class="th text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr class="table-row">
                        <td class="td font-mono font-semibold">{{ $account->account_code }}</td>
                        <td class="td font-medium">{{ $account->account_name }}</td>
                        <td class="td">
                            <span class="badge {{ $typeColors[$account->type] ?? 'badge-slate' }}">
                                {{ $typeLabels[$account->type] ?? $account->type }}
                            </span>
                        </td>
                        <td class="td text-center tabular-nums">{{ $account->journal_details_count }}</td>
                        <td class="td text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('accounts.edit', $account) }}" class="btn btn-warning btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </a>
                                <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="inline"
                                      data-confirm="Yakin ingin menghapus akun {{ $account->account_code }} - {{ $account->account_name }}?"
                                      data-confirm-label="Hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="td text-center py-10 text-ink/50">Belum ada akun.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
