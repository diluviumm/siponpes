@extends('layouts.app')

@section('page-title', 'Tempat Sampah Santri')

@section('content')
<div class="max-w-6xl mx-auto">

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Data Santri', 'url' => route('santri.index')],
        'Tempat Sampah',
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tempat Sampah Santri</h1>
            <p class="page-subtitle">Santri yang telah dihapus dapat dipulihkan kembali.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('santri.index', auth()->user()->isSuperadmin() && request('school_id') ? ['school_id' => request('school_id')] : []) }}" class="btn btn-ghost btn-md">
                Kembali ke Daftar Santri
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('santri.trash') }}" class="mb-6">
        <div class="flex items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIS santri yang dihapus..."
                   class="input flex-1 max-w-xs">
            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
            @if(request('q'))
                <a href="{{ route('santri.trash', auth()->user()->isSuperadmin() && request('school_id') ? ['school_id' => request('school_id')] : []) }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </div>
        @if(auth()->user()->isSuperadmin() && request('school_id'))
            <input type="hidden" name="school_id" value="{{ request('school_id') }}">
        @endif
    </form>

    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed">
                <colgroup>
                    <col>
                    <col class="w-28 hidden sm:table-cell">
                    <col class="w-40 hidden md:table-cell">
                    <col class="w-44 hidden md:table-cell">
                    <col class="w-40">
                </colgroup>
                <thead>
                    <tr>
                        <th class="th">Nama</th>
                        <th class="th hidden sm:table-cell">NIS</th>
                        <th class="th hidden md:table-cell">Kelas</th>
                        <th class="th hidden md:table-cell">Dihapus Pada</th>
                        <th class="th">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($santris as $santri)
                        <tr class="table-row">
                            <td class="td font-medium text-ink truncate">{{ $santri->nama }}</td>
                            <td class="td hidden sm:table-cell text-ink/70 truncate">{{ $santri->nis ?? '-' }}</td>
                            <td class="td hidden md:table-cell text-ink/70 truncate">{{ $santri->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="td hidden md:table-cell text-ink/50 text-sm truncate">{{ $santri->deleted_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                            <td class="td">
                                <form method="POST" action="{{ route('santri.restore', $santri->id) }}" class="inline"
                                      data-confirm="Pulihkan santri {{ $santri->nama }}?"
                                      data-confirm-label="Pulihkan">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Pulihkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="td text-center py-8 text-ink/50">
                                @if(request('q'))
                                    Tidak ditemukan santri terhapus dengan kata kunci tersebut.
                                @else
                                    Belum ada santri yang dihapus.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($santris->hasPages())
            <div class="px-4 py-3 border-t border-ink/10">
                {{ $santris->links() }}
            </div>
        @endif
    </div>
</div>
@endsection