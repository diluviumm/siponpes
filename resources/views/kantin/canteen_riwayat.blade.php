@extends('layouts.app')

@section('title', "Riwayat Hapus {$canteen->name}")

@section('page-title', "Riwayat Hapus {$canteen->name}")

@section('content')
@php $isSuper = auth()->user()->isSuperadmin(); @endphp

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
    ['label' => 'Kasir Kantin', 'url' => route('kantin.index', $isSuper ? ['school_id' => $school->id] : [])],
    ['label' => 'Kelola Kantin & Menu', 'url' => route('kantin.manage', $isSuper ? ['school_id' => $school->id] : [])],
    ['label' => $canteen->name, 'url' => route('kantin.manage.show', $canteen)],
    'Riwayat Hapus',
]" />

<div class="page-header">
    <div>
        <h1 class="page-title">Riwayat Hapus</h1>
        <x-page-title-rule width="max-w-xs" />
        <p class="page-subtitle">{{ $canteen->name }} — riwayat penghapusan kantin ini beserta menu-menunya. Data yang dihapus bisa dipulihkan.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('kantin.manage', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali ke Kelola Kantin</a>
    </div>
</div>

@if($canteenLogs->isEmpty() && $itemLogs->isEmpty())
    <div class="card card-body text-center text-ink/60">Belum ada data yang dihapus pada kantin ini.</div>
@else
    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-cream">
                    <tr>
                        <th class="th">Waktu Dihapus</th>
                        <th class="th">Objek</th>
                        <th class="th">Nama</th>
                        <th class="th">Oleh</th>
                        <th class="th text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($canteenLogs as $log)
                        <tr class="table-row">
                            <td class="td tabular-nums whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="td"><x-badge tone="blue">Kantin</x-badge></td>
                            <td class="td font-medium">{{ $canteen->name }}</td>
                            <td class="td whitespace-nowrap">{{ $log->user?->name ?? '-' }}</td>
                            <td class="td text-center whitespace-nowrap">
                                <span class="text-ink/40 text-xs">Sudah dipulihkan / aktif</span>
                            </td>
                        </tr>
                    @endforeach

                    @foreach($itemLogs as $log)
                        @php
                            $item = $items->get($log->auditable_id);
                            $trashed = $item && $item->trashed();
                        @endphp
                        <tr class="table-row">
                            <td class="td tabular-nums whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="td"><x-badge tone="slate">Menu</x-badge></td>
                            <td class="td font-medium">{{ $item?->name ?? $log->summary ?? '-' }}</td>
                            <td class="td whitespace-nowrap">{{ $log->user?->name ?? '-' }}</td>
                            <td class="td text-center whitespace-nowrap">
                                @if($trashed)
                                    <form action="{{ route('kantin.item.restore', $log->auditable_id) }}" method="POST"
                                          data-confirm="Pulihkan menu {{ $item->name }}?"
                                          data-confirm-label="Pulihkan">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Pulihkan</button>
                                    </form>
                                @else
                                    <span class="text-ink/40 text-xs">Sudah dipulihkan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
