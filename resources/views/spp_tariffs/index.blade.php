@extends('layouts.app')

@section('title', $school ? "Tarif SPP {$school->name}" : 'Tarif SPP')

@section('page-title', $school ? "Tarif SPP {$school->name}" : 'Tarif SPP')

@section('content')

<x-school-filter :schools="$schools" route="spp_tariffs.index" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Tarif SPP']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tarif SPP</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk mengelola tarif SPP.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk mengelola tarif SPP.
    </div>

@else

    @php $isSuper = auth()->user()->isSuperadmin(); @endphp

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Tarif SPP', 'url' => route('spp_tariffs.index')] : 'Tarif SPP',
        $isSuper ? $school->name : null,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tarif SPP</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Atur tarif SPP per bulan atau per semester — semua kelas, per kelas, atau per angkatan — {{ $school->name }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('spp_tariffs.create', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-primary btn-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tarif
            </a>
        </div>
    </div>

    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="th">Terapan</th>
                        <th class="th">Tahun Ajaran</th>
                        <th class="th text-right">Jumlah</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tariffs as $tariff)
                        <tr class="table-row">
                            <td class="td font-medium">{{ $tariff->type === 'angkatan' ? 'Kelas '.$tariff->angkatan : ($tariff->kelas?->nama_kelas ?? 'Semua Kelas') }}</td>
                            <td class="td tabular-nums">{{ $tariff->academic_year }}</td>
                            <td class="td text-right font-semibold tabular-nums">Rp {{ number_format($tariff->amount, 2, ',', '.') }}</td>
                            <td class="td text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('spp_tariffs.edit', $tariff) }}" class="btn btn-warning btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                    <form action="{{ route('spp_tariffs.destroy', $tariff) }}" method="POST" class="inline"
                                          data-confirm="Yakin ingin menghapus tarif {{ $tariff->kelas?->nama_kelas ?? 'Semua Kelas' }}?"
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
                            <td colspan="4" class="td text-center py-10 text-ink/50">
                                Belum ada tarif SPP untuk sekolah ini.
                                <a href="{{ route('spp_tariffs.create', $isSuper ? ['school_id' => $school->id] : []) }}" class="text-pine-700 hover:underline font-medium">Buat tarif pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $tariffs->links() }}
    </div>

@endif
@endsection
