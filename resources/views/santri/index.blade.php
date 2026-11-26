@extends('layouts.app')

@section('title', $school ? "Santri {$school->name}" : 'Data Santri')

@section('page-title', $school ? "Santri {$school->name}" : 'Data Santri')

@section('content')

<x-school-filter :schools="$schools" route="santri.index" :current="$school?->id" />

@if(! $school)

    {{-- Pilih Sekolah (superadmin) --}}
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Data Santri']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Data Santri</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk melihat data santri.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk melihat data santri.
    </div>

@else

    {{-- Pencarian --}}
    <form method="GET" class="mb-6" id="global-search-form">
        @if($kelasId)
            <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
        @endif
        @if(auth()->user()->isSuperadmin())
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <div class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Cari nama atau NIS santri..."
                    class="input pl-10">
            </div>
            <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
            @if($search)
                <a href="{{ route('santri.index', array_filter(['school_id' => auth()->user()->isSuperadmin() ? $school->id : null, 'kelas_id' => $showAll ? 'all' : null])) }}"
                   class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
            @endif
        </div>
    </form>

    <div class="mb-6 -mt-3">
        <a href="{{ route('santri.trash', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}"
           class="inline-flex items-center gap-1.5 text-sm text-ink/60 hover:text-pine-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Tempat Sampah
            @if($trashCount > 0)
                <span class="text-xs px-1.5 py-0.5 rounded-full bg-ink/10 text-ink/70 tabular-nums">{{ $trashCount }}</span>
            @endif
        </a>
    </div>

    {{-- Breadcrumb --}}
    @php
        $isSuper = auth()->user()->isSuperadmin();
        $crumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
            $isSuper || $selectedKelas
                ? ['label' => 'Data Santri', 'url' => route('santri.index', $isSuper ? [] : [])]
                : 'Data Santri',
        ];

        if ($isSuper && $selectedKelas) {
            $crumbs[] = ['label' => $school->name, 'url' => route('santri.index', ['school_id' => $school->id])];
        } elseif ($isSuper) {
            $crumbs[] = $school->name;
        }

        if ($selectedKelas) {
            $crumbs[] = $selectedKelas->nama_kelas;
        }

        if ($search) {
            $crumbs[] = 'cari "' . $search . '"';
        }
    @endphp
    <x-breadcrumb :items="$crumbs" />

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $selectedKelas->nama_kelas ?? $school->name }}</h1>
            <p class="page-subtitle">
                @if($selectedKelas)
                    {{ $selectedKelas->wali_kelas }} · {{ $santris->total() }} santri
                @elseif($showAll)
                    {{ $school->name }} · {{ $santris->total() }} santri
                @else
                    {{ $school->name }} · {{ $kelasList->count() }} kelas
                @endif
            </p>
        </div>
        <div class="page-actions">
            @if($showAll)
                <a href="{{ route('santri.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Pilih Kelas
                </a>
            @endif
            @php
                $exportParams = array_filter([
                    'school_id' => auth()->user()->isSuperadmin() ? $school->id : null,
                    'kelas_id' => $kelasId,
                    'search' => $search,
                ], fn ($v) => $v !== null && $v !== '');
            @endphp
            <x-export-actions :buttons="[
                ['label' => 'Export CSV', 'url' => route('santri.index', $exportParams + ['csv' => 1])],
                ['label' => 'Export PDF', 'url' => route('santri.index', $exportParams + ['pdf' => 1])],
            ]" />
            <a href="{{ route('santri.import', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Excel
            </a>
            <a href="{{ route('santri.create', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-primary btn-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Santri
            </a>
            @if($selectedKelas && $selectedKelas->santris_count > 0)
                <form action="{{ route('santri.destroy-by-kelas', $selectedKelas) }}" method="POST" class="inline"
                      data-confirm="Hapus {{ $selectedKelas->santris_count }} santri dari kelas {{ $selectedKelas->nama_kelas }}? Wallet &amp; riwayat transaksi tetap disimpan."
                      data-confirm-label="Hapus">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Semua Santri
                    </button>
                </form>
            @endif
            @if($showAll)
                <form action="{{ route('santri.destroy-all', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" method="POST" class="inline"
                      data-confirm="Hapus SEMUA santri di sekolah ini? Tindakan ini akan menghapus {{ $santris->total() }} santri. Wallet &amp; riwayat transaksi tetap disimpan."
                      data-confirm-label="Hapus Semua">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Semua Siswa
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($isGlobalSearch)

        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="th">NIS</th>
                            <th class="th">Nama</th>
                            <th class="th">Kelas</th>
                            <th class="th">Jenis Kelamin</th>
                            <th class="th text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($santris as $santri)
                            <tr class="table-row">
                                <td class="td font-mono">{{ $santri->nis ?? '-' }}</td>
                                <td class="td font-medium">{{ $santri->nama }}</td>
                                <td class="td">
                                    <a href="{{ route('santri.index', ['school_id' => $school->id, 'kelas_id' => $santri->kelas_id]) }}" class="text-pine-700 hover:underline font-medium">
                                        {{ $santri->kelas?->nama_kelas ?? '-' }}
                                    </a>
                                </td>
                                <td class="td">
                                    @if($santri->jenis_kelamin === 'L')
                                        <x-badge tone="blue">Laki-laki</x-badge>
                                    @elseif($santri->jenis_kelamin === 'P')
                                        <x-badge tone="rose">Perempuan</x-badge>
                                    @else
                                        <span class="text-ink/40">-</span>
                                    @endif
                                </td>
                                <td class="td text-center whitespace-nowrap">
                                    @include('santri._actions', ['santri' => $santri, 'school' => $school])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="td text-center py-10 text-ink/50">
                                    @if($search)
                                        Tidak ada santri dengan kata kunci "<strong>{{ $search }}</strong>".
                                    @else
                                        Belum ada santri di sekolah ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $santris->links() }}</div>

    @elseif($selectedKelas)

        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="th">NIS</th>
                            <th class="th">Nama</th>
                            <th class="th">Jenis Kelamin</th>
                            <th class="th text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($santris as $santri)
                            <tr class="table-row">
                                <td class="td font-mono">{{ $santri->nis ?? '-' }}</td>
                                <td class="td font-medium">{{ $santri->nama }}</td>
                                <td class="td">
                                    @if($santri->jenis_kelamin === 'L')
                                        <x-badge tone="blue">Laki-laki</x-badge>
                                    @elseif($santri->jenis_kelamin === 'P')
                                        <x-badge tone="rose">Perempuan</x-badge>
                                    @else
                                        <span class="text-ink/40">-</span>
                                    @endif
                                </td>
                                <td class="td text-center whitespace-nowrap">
                                    @include('santri._actions', ['santri' => $santri, 'school' => $school])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="td text-center py-10 text-ink/50">
                                    @if($search)
                                        Tidak ada santri dengan kata kunci "<strong>{{ $search }}</strong>".
                                    @else
                                        Belum ada santri di kelas ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $santris->links() }}</div>

    @else

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('santri.index', array_filter(['school_id' => auth()->user()->isSuperadmin() ? $school->id : null, 'kelas_id' => 'all'])) }}"
               class="school-select border-pine-200">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h2 class="font-display text-lg font-semibold text-pine-900">Semua Santri</h2>
                        <p class="text-sm text-ink/60 mt-0.5">Seluruh kelas</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-display font-semibold text-pine-700 tabular-nums">{{ $kelasList->sum('santris_count') }}</p>
                        <p class="text-xs text-ink/50">Santri</p>
                    </div>
                </div>
                <div class="flex items-center text-pine-700 text-sm font-medium">
                    Lihat Semua
                    <svg class="w-4 h-4 ml-1 transition-transform hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>
            @forelse($kelasList as $kelas)
                <a href="{{ route('santri.index', array_filter(['school_id' => auth()->user()->isSuperadmin() ? $school->id : null, 'kelas_id' => $kelas->id])) }}"
                   class="school-select">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h2 class="font-display text-lg font-semibold text-pine-900">{{ $kelas->nama_kelas }}</h2>
                            <p class="text-sm text-ink/60 mt-0.5">{{ $kelas->wali_kelas }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-display font-semibold text-pine-700 tabular-nums">{{ $kelas->santris_count }}</p>
                            <p class="text-xs text-ink/50">Santri</p>
                        </div>
                    </div>
                    <div class="flex items-center text-pine-700 text-sm font-medium">
                        Lihat Santri
                        <svg class="w-4 h-4 ml-1 transition-transform hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12 text-ink/50 card">
                    Belum ada kelas di sekolah ini.
                    <a href="{{ route('kelas.create', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="text-pine-700 hover:underline font-medium">Buat kelas baru</a>
                </div>
            @endforelse
        </div>

    @endif

@endif

@endsection
