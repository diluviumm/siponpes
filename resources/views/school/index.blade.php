@extends('layouts.app')

@section('title', 'Data Sekolah')

@section('page-title', 'Data Sekolah')

@section('content')

<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Data Sekolah']" />

<div class="page-header">
    <div>
        <h1 class="page-title">Data Sekolah</h1>
        <x-page-title-rule />
        <p class="page-subtitle">Kelola sekolah terdaftar dan setujui pendaftaran baru.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('schools.create') }}" class="btn btn-primary btn-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Sekolah
        </a>
    </div>
</div>

{{-- Pencarian & sortir --}}
<form method="GET" class="mb-6" id="school-search-form">
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif
    @if(request('direction'))
        <input type="hidden" name="direction" value="{{ request('direction') }}">
    @endif
    <div class="flex gap-3 flex-wrap">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cari nama sekolah, alamat, atau email admin..."
                class="input pl-10">
        </div>
        <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
        @if(request('search') || request('sort'))
            <a href="{{ route('schools.index') }}" class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
        @endif
    </div>
</form>

{{-- Menunggu Persetujuan --}}
@php
    $pending = $schools->where('status', 'pending');
@endphp
@if($pending->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gold-700 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Menunggu Persetujuan ({{ $pending->count() }})
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($pending as $school)
                <div class="card p-5 border-gold-300">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-ink truncate">{{ $school->name }}</h3>
                            <p class="text-xs text-ink/60 mt-1">{{ $school->address ?? '-' }}</p>
                            <p class="text-xs text-ink/60">{{ $school->phone ?? '-' }}</p>
                        </div>
                        <x-badge tone="yellow">Menunggu</x-badge>
                    </div>
                    @if($school->users()->first())
                        <p class="text-xs text-ink/50 mt-3">Admin: {{ $school->users()->first()->name }} · {{ $school->users()->first()->email }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form action="{{ route('schools.approve', $school) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Setujui
                            </button>
                        </form>
                        <form action="{{ route('schools.reject', $school) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                        </form>
                        <a href="{{ route('schools.edit', $school) }}" class="btn btn-ghost btn-sm">Detail</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Daftar Sekolah --}}
<div class="table-card">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="th"><a href="{{ $sortUrl('name') }}" class="hover:text-pine-700 inline-flex items-center gap-1">Sekolah @include('components._sort-indicator', ['column' => 'name'])</a></th>
                    <th class="th">Alamat</th>
                    <th class="th"><a href="{{ $sortUrl('status') }}" class="hover:text-pine-700 inline-flex items-center gap-1">Status @include('components._sort-indicator', ['column' => 'status'])</a></th>
                    <th class="th text-center"><a href="{{ $sortUrl('santris_count') }}" class="hover:text-pine-700 inline-flex items-center gap-1">Santri @include('components._sort-indicator', ['column' => 'santris_count'])</a></th>
                    <th class="th text-center">Kelas</th>
                    <th class="th text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools->where('status', '!=', 'pending') as $school)
                    <tr class="table-row">
                        <td class="td font-medium">{{ $school->name }}</td>
                        <td class="td">{{ $school->address ?? '-' }}</td>
                        <td class="td">
                            @if($school->status === 'active')
                                <x-badge tone="green">Aktif</x-badge>
                            @else
                                <x-badge tone="red">Ditolak</x-badge>
                            @endif
                        </td>
                        <td class="td text-center tabular-nums">{{ $school->santris_count }}</td>
                        <td class="td text-center tabular-nums">{{ $school->kelas_count }}</td>
                        <td class="td text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                @if($school->status !== 'active')
                                    <form action="{{ route('schools.approve', $school) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Aktifkan</button>
                                    </form>
                                @else
                                    <form action="{{ route('schools.reject', $school) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">Nonaktifkan</button>
                                    </form>
                                @endif
                                <a href="{{ route('schools.edit', $school) }}" class="btn btn-ghost btn-sm">Kelola</a>
                                <a href="{{ route('schools.edit', $school) }}#hapus-permanen" class="btn btn-danger btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus Permanen
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="td text-center py-10 text-ink/50">
                            Belum ada sekolah. Tambahkan lewat tombol di atas atau tunggu pendaftaran baru.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
