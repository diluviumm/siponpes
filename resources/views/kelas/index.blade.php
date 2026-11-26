@extends('layouts.app')

@section('title', $school ? "Kelas {$school->name}" : 'Data Kelas')

@section('page-title', $school ? "Kelas {$school->name}" : 'Data Kelas')

@section('content')

<x-school-filter :schools="$schools" route="kelas.index" :current="$school?->id" />

@if(! $school)

    {{-- Pilih Sekolah (superadmin) --}}
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Data Kelas']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Data Kelas</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk mengelola kelas.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk melihat data kelas.
    </div>

@else

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $school->name }}</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Kelola kelas, wali kelas, dan kapasitas santri.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('kelas.create', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-primary btn-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Kelas
            </a>
            @if($kelas->isNotEmpty())
                <form action="{{ route('kelas.destroy-all', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" method="POST" class="inline"
                      data-confirm="Hapus semua kelas beserta santrinya? Tindakan ini permanen (soft delete)."
                      data-confirm-label="Hapus Semua">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Semua Kelas
                    </button>
                </form>
            @endif
        </div>
    </div>

    @php
        $isSuper = auth()->user()->isSuperadmin();
    @endphp
    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Data Kelas', 'url' => route('kelas.index')] : 'Data Kelas',
        $isSuper ? $school->name : null,
    ]" />

    {{-- Pencarian --}}
    <form method="GET" class="mb-6">
        @if(auth()->user()->isSuperadmin())
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <div class="flex gap-3 flex-wrap">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama kelas..."
                    class="input pl-10">
            </div>
            <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
            @if(request('search'))
                <a href="{{ route('kelas.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
            @endif
        </div>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($kelas as $item)
            <div class="card p-5 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h2 class="font-display text-lg font-semibold text-pine-900">{{ $item->nama_kelas }}</h2>
                        <p class="text-sm text-ink/60 mt-0.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="truncate">{{ $item->wali_kelas }}</span>
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-2xl font-display font-semibold text-pine-700 tabular-nums">{{ $item->santris_count }}</p>
                        <p class="text-xs text-ink/50">Santri</p>
                    </div>
                </div>

                @if($item->kapasitas)
                    @php
                        $kapasitas = (int) $item->kapasitas;
                        $terisi = (int) $item->santris_count;
                        $persen = $kapasitas > 0 ? round(($terisi / $kapasitas) * 100) : 0;
                        $penuh = $kapasitas > 0 && $terisi >= $kapasitas;
                        $melebihi = $kapasitas > 0 && $terisi > $kapasitas;
                    @endphp
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-ink/70 mb-1">
                            <span>Kapasitas: {{ $kapasitas }}</span>
                            <span class="{{ $penuh ? 'text-rose-700 font-semibold' : '' }}">{{ min($persen, 100) }}%</span>
                        </div>
                        <div class="w-full bg-pine-50 rounded-full h-2 overflow-hidden">
                            <div class="{{ $penuh ? 'bg-rose-600' : 'bg-pine-600' }} h-2 rounded-full transition-all"
                                 style="width: {{ min($persen, 100) }}%">
                            </div>
                        </div>
                        @if($melebihi)
                            <p class="text-xs text-rose-700 mt-1">Melebihi kapasitas ({{ $terisi }}/{{ $kapasitas }}). Pindahkan sebagian santri ke kelas lain.</p>
                        @elseif($penuh)
                            <p class="text-xs text-rose-700 mt-1">Kelas penuh ({{ $terisi }}/{{ $kapasitas }}).</p>
                        @endif
                    </div>
                @endif

                @if($item->keterangan)
                    <p class="text-sm text-ink/50 mb-4">{{ $item->keterangan }}</p>
                @endif

                <div class="flex items-center gap-2 pt-4 mt-auto border-t border-ink/10">
                    <a href="{{ route('santri.index', array_filter(['school_id' => auth()->user()->isSuperadmin() ? $school->id : null, 'kelas_id' => $item->id])) }}"
                       class="btn btn-info btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Lihat Santri
                    </a>
                    <a href="{{ route('kelas.edit', $item->id) }}" class="btn btn-warning btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </a>
                    <form action="{{ route('kelas.destroy', $item->id) }}"
                          method="POST" class="inline"
                          data-confirm="Hapus kelas {{ $item->nama_kelas }}? Semua santri di kelas ini juga akan terhapus."
                          data-confirm-label="Hapus">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-ink/50 card">
                Belum ada data kelas di sekolah ini.
                <a href="{{ route('kelas.create', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="text-pine-700 hover:underline font-medium">Buat kelas baru</a>
            </div>
        @endforelse
    </div>

@endif

@endsection
