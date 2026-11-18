@extends('layouts.app')

@section('title', $school ? "Tabungan {$school->name}" : 'Tabungan Santri')

@section('page-title', $school ? "Tabungan {$school->name}" : 'Tabungan Santri')

@section('content')

<x-school-filter :schools="$schools" route="wallet.index" :current="$school?->id" />

@if(! $school)

    {{-- Pilih Sekolah (superadmin) --}}
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Tabungan Santri']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Tabungan Santri</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk melihat tabungan santri.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk melihat tabungan santri.
    </div>

@else

    {{-- Pencarian --}}
    <form method="GET" class="mb-6">
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
                <a href="{{ route('wallet.index', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}"
                   class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
            @endif
        </div>
    </form>

    {{-- Breadcrumb --}}
    @php
        $isSuper = auth()->user()->isSuperadmin();
        $crumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
            $isSuper || $selectedKelas
                ? ['label' => 'Tabungan Santri', 'url' => route('wallet.index', $isSuper ? [] : [])]
                : 'Tabungan Santri',
        ];

        if ($isSuper && $selectedKelas) {
            $crumbs[] = ['label' => $school->name, 'url' => route('wallet.index', ['school_id' => $school->id])];
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

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $selectedKelas->nama_kelas ?? $school->name }}</h1>
            <p class="page-subtitle">
                @if($selectedKelas)
                    {{ $selectedKelas->wali_kelas }} · {{ $santris->total() }} santri
                @else
                    {{ $school->name }} · {{ $kelasList->count() }} kelas
                @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('santri.import', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Excel
            </a>
            <div class="bg-pine-50 border border-pine-100 rounded-lg px-4 py-2 text-right">
                <p class="text-xs text-ink/60">Total Saldo</p>
                <p class="text-lg font-display font-semibold text-pine-800 tabular-nums">Rp {{ number_format($totalSaldo, 2, ',', '.') }}</p>
            </div>
            <div class="bg-sky-50 border border-sky-100 rounded-lg px-4 py-2 text-right">
                <p class="text-xs text-ink/60">Total Limit</p>
                <p class="text-lg font-display font-semibold text-sky-800 tabular-nums">Rp {{ number_format($totalLimit, 2, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="card card-body mb-6 border-gold-300 bg-gold-50/50 text-sm text-ink/80">
        <p><strong>Perhatian:</strong> Saldo tabungan santri adalah milik santri/wali yang <strong>dititipkan</strong> ke sekolah, bukan kas sekolah. Dana ini tidak bisa dipakai untuk operasional sekolah. Baru setelah dibayarkan sebagai SPP, dana menjadi pendapatan sekolah.</p>
    </div>

    @if($isGlobalSearch || $selectedKelas)

        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                    <tr>
                        <th class="th">NIS</th>
                        <th class="th">Nama</th>
                        @if($isGlobalSearch)
                            <th class="th">Kelas</th>
                        @endif
                        <th class="th text-right">Saldo</th>
                        <th class="th text-right">Limit Harian</th>
                        <th class="th text-center">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($santris as $santri)
                        <tr class="table-row">
                            <td class="td font-mono">{{ $santri->nis ?? '-' }}</td>
                            <td class="td font-medium">{{ $santri->nama }}</td>
                            @if($isGlobalSearch)
                                <td class="td">
                                    <a href="{{ route('wallet.index', ['school_id' => $school->id, 'kelas_id' => $santri->kelas_id]) }}" class="text-pine-700 hover:underline font-medium">
                                        {{ $santri->kelas?->nama_kelas ?? '-' }}
                                    </a>
                                </td>
                            @endif
                            <td class="td text-right font-semibold tabular-nums {{ ($santri->wallet?->balance ?? 0) > 0 ? 'text-pine-700' : 'text-ink/50' }}">
                                Rp {{ number_format($santri->wallet?->balance ?? 0, 2, ',', '.') }}
                            </td>
                            <td class="td text-right tabular-nums">
                                Rp {{ number_format($santri->wallet?->daily_spending_limit ?? 0, 2, ',', '.') }}
                            </td>
                            <td class="td text-center whitespace-nowrap">
                                @include('wallet._actions', ['santri' => $santri, 'school' => $school])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isGlobalSearch ? 6 : 5 }}" class="td text-center py-10 text-ink/50">
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
            @forelse($kelasList as $kelas)
                <a href="{{ route('wallet.index', array_filter(['school_id' => auth()->user()->isSuperadmin() ? $school->id : null, 'kelas_id' => $kelas->id])) }}"
                   class="school-select">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-display text-lg font-semibold text-pine-900">{{ $kelas->nama_kelas }}</h4>
                            <p class="text-sm text-ink/60 mt-0.5">{{ $kelas->wali_kelas }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-display font-semibold text-pine-700 tabular-nums">{{ $kelas->santris_count }}</p>
                            <p class="text-xs text-ink/50">Santri</p>
                        </div>
                    </div>
                    <div class="space-y-1 text-sm">
                        <p class="text-ink/70">Saldo: <strong class="text-pine-700 tabular-nums">Rp {{ number_format($kelas->total_saldo, 2, ',', '.') }}</strong></p>
                        <p class="text-ink/70">Limit: <strong class="text-sky-700 tabular-nums">Rp {{ number_format($kelas->total_limit, 2, ',', '.') }}</strong></p>
                    </div>
                    <div class="flex items-center text-pine-700 text-sm font-medium mt-3">
                        Lihat Tabungan
                        <svg class="w-4 h-4 ml-1 transition-transform hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12 text-ink/50 card">
                    Belum ada kelas di sekolah ini.
                    @if(isset($school))
                        <br><a href="{{ route('kelas.create', auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : []) }}" class="text-pine-700 hover:underline text-sm font-medium">Buat kelas baru</a>
                    @endif
                </div>
            @endforelse
        </div>

    @endif

@endif

@endsection
