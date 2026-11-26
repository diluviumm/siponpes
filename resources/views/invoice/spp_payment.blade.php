@extends('layouts.app')

@section('title', 'Pembayaran SPP')

@section('page-title', 'Pembayaran SPP')

@section('content')

<x-school-filter :schools="$schools" route="invoices.spp-payment" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard', auth()->user()->isSuperadmin() ? [] : [])], 'Pembayaran SPP']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Pembayaran SPP</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk memulai pembayaran SPP.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu.
    </div>

@else

    @php $isSuper = auth()->user()->isSuperadmin(); @endphp

    <x-breadcrumb :items="array_merge(
        [['label' => 'Dashboard', 'url' => route('dashboard', $isSuper && $school ? ['school_id' => $school->id] : [])]],
        $selectedKelas
            ? [['label' => 'Pembayaran SPP', 'url' => route('invoices.spp-payment', $isSuper && $school ? ['school_id' => $school->id] : [])], 'Kelas '.$selectedKelas->nama_kelas]
            : ['Pembayaran SPP']
    )" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Pembayaran SPP</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }}{{ $selectedKelas ? ' — Kelas '.$selectedKelas->nama_kelas : ' — pilih kelas untuk memulai pembayaran.' }}</p>
        </div>
        <a href="{{ route('invoices.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">
            Riwayat Invoice
        </a>
    </div>

    @if(! $hasTariffs)
        <x-flash type="info">
            Belum tersedia Tarif SPP untuk sekolah ini. Silakan tambahkan Tarif SPP terlebih dahulu melalui menu Tarif SPP.
        </x-flash>
    @endif

    @php
        $tahunAwalBatch = null;
        if ($academicYearBatch && preg_match('/^(\d{4})\/\d{4}$/', $academicYearBatch, $m)) {
            $tahunAwalBatch = (int) $m[1];
        }
    @endphp

    {{-- Bayar Semua Semua Kelas (lintas kelas) --}}
    @if($school && !$selectedKelas && $hasTariffs)
        @php
            $totalSantri = $kelasList->sum('santris_count');
        @endphp
        @if($totalSantri > 0)
            <div class="card card-body mb-6 border-gold-300">
                <div class="flex items-start justify-between flex-wrap gap-3">
                    <div>
                        <h2 class="font-display font-semibold text-pine-900">Bayar Massal</h2>
                        <p class="text-sm text-ink/60">Pilih periode, lalu bayar untuk semua santri bersaldo cukup di seluruh kelas.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('invoices.spp-pay-batch') }}"
                      class="mt-4 pt-4 border-t border-gold-200 flex items-center gap-2 flex-wrap"
                      onsubmit="return siponpesConfirm(event, 'Bayar SPP ' + this.querySelector('select[name=period_value] option:checked').text + ' untuk semua santri bersaldo cukup di {{ $school->name }}?')">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $school->id }}">
                    <input type="hidden" name="periode_type" value="{{ $periodeTypeBatch }}">
                    <input type="hidden" name="period_number" value="{{ $periodeTypeBatch === 'semester' ? '1' : now()->month }}">
                    <label class="text-sm font-medium text-ink/70">Periode</label>
                    <select name="period_value" class="input text-sm py-1 w-44 tabular-nums"
                            onchange="const p=this.value.split('|'); this.form.periode_type.value=p[0]; this.form.period_number.value=p[1];">
                        @if($periodeTypeBatch === 'semester')
                            <option value="semester|1" {{ now()->month <= 6 ? 'selected' : '' }}>Semester 1 (Ganjil) {{ $academicYearBatch }}</option>
                            <option value="semester|2" {{ now()->month > 6 ? 'selected' : '' }}>Semester 2 (Genap) {{ $academicYearBatch }}</option>
                        @else
                            @php $bulanUrut = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6]; @endphp
                            @foreach($bulanUrut as $b)
                                @php
                                    $tahunBulan = ($tahunAwalBatch && $b >= 1 && $b <= 6) ? $tahunAwalBatch + 1 : ($tahunAwalBatch ?? now()->year);
                                @endphp
                                <option value="bulanan|{{ $b }}" {{ $b == now()->month ? 'selected' : '' }}>{{ $bulanIndo[$b] }} {{ $tahunBulan }}</option>
                            @endforeach
                        @endif
                    </select>
                    <button type="submit" class="btn btn-accent">Bayar</button>
                </form>
            </div>
        @endif
    @endif

    {{-- Pilih kelas --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-6">
        @forelse($kelasList as $kelas)
            @php $selected = $kelasId == $kelas->id; @endphp
            <a href="{{ route('invoices.spp-payment', $isSuper ? ['school_id' => $school->id, 'kelas_id' => $kelas->id] : ['kelas_id' => $kelas->id]) }}"
               class="stat-card {{ $selected ? 'ring-2 ring-pine-500' : '' }}">
                <p class="stat-card-label">{{ $kelas->nama_kelas }}</p>
                <p class="stat-card-value text-lg">{{ $kelas->santris_count }} santri</p>
            </a>
        @empty
            <p class="col-span-full text-center text-ink/60">Belum ada kelas.</p>
        @endforelse
    </div>

    @if($selectedKelas && $santris->isNotEmpty() && $hasTariffs)
            <div class="card card-body">
                @php
                    $adaSaldoCukup = $santris->contains(function ($s) use ($tariffByKelas, $tariffByAngkatan, $tariffSemua, $nextPeriods) {
                        $tarif = $tariffByKelas[$s->kelas_id] ?? $tariffByAngkatan[$s->angkatan] ?? $tariffSemua;
                        $saldo = $s->wallet?->balance ?? 0;
                        return $saldo >= $tarif && ($nextPeriods[$s->id] ?? null);
                    });
                @endphp
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <h2 class="font-display font-semibold text-pine-900">Kelas {{ $selectedKelas->nama_kelas }}</h2>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <form method="GET" action="{{ route('invoices.spp-payment', $isSuper ? ['school_id' => $school->id, 'kelas_id' => $selectedKelas->id] : ['kelas_id' => $selectedKelas->id]) }}" class="flex gap-2">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari santri..." class="input text-sm py-1.5 w-48">
                            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        </form>
                        @if($adaSaldoCukup)
                            <span class="text-ink/40">|</span>
                            <form method="POST" action="{{ route('invoices.spp-pay-batch') }}" class="inline-flex gap-2 items-center"
                                  onsubmit="return siponpesConfirm(event, 'Bayar SPP ' + this.querySelector('select[name=period_value] option:checked').text + ' untuk semua santri bersaldo cukup di kelas {{ $selectedKelas->nama_kelas }}?')">
                                @csrf
                                <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                                <input type="hidden" name="periode_type" value="{{ $periodeTypeBatch }}">
                                <input type="hidden" name="period_number" value="{{ $periodeTypeBatch === 'semester' ? '1' : now()->month }}">
                                <select name="period_value" class="input text-xs py-1 w-36 tabular-nums"
                                        onchange="const p=this.value.split('|'); this.form.periode_type.value=p[0]; this.form.period_number.value=p[1];">
                                    @if($periodeTypeBatch === 'semester')
                                        <option value="semester|1" {{ now()->month <= 6 ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                                        <option value="semester|2" {{ now()->month > 6 ? 'selected' : '' }}>Semester 2 (Genap)</option>
                                    @else
                                        @php $bulanUrut = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6]; @endphp
                                        @foreach($bulanUrut as $b)
                                            <option value="bulanan|{{ $b }}" {{ $b == now()->month ? 'selected' : '' }}>{{ $bulanIndo[$b] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <button type="submit" class="btn btn-accent btn-sm">Bayar</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="table-card">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="th">Nama</th>
                                <th class="th">NIS</th>
                                <th class="th text-right">Saldo Wallet</th>
                                <th class="th text-right">Tarif</th>
                                <th class="th text-center">Periode</th>
                                <th class="th text-center">Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($santris as $s)
                                @php
                                    $tarif = $tariffByKelas[$s->kelas_id] ?? $tariffByAngkatan[$s->angkatan] ?? $tariffSemua;
                                    $saldo = $s->wallet?->balance ?? 0;
                                    $saldoCukup = $saldo >= $tarif;
                                    $next = $nextPeriods[$s->id] ?? null;
                                @endphp
                                <tr class="table-row">
                                    <td class="td font-medium">{{ $s->nama }}</td>
                                    <td class="td tabular-nums text-ink/60">{{ $s->nis ?? '-' }}</td>
                                    <td class="td text-right tabular-nums {{ $saldo <= 0 ? 'text-rose-600' : 'text-pine-700' }}">
                                        Rp {{ number_format($saldo, 0, ',', '.') }}
                                    </td>
                                    <td class="td text-right tabular-nums">Rp {{ number_format($tarif, 0, ',', '.') }}</td>
                                    <td class="td text-xs tabular-nums text-center">
                                        @if($next)
                                            {{ $next['label'] }}
                                        @else
                                            <span class="text-ink/40">Lunas</span>
                                        @endif
                                    </td>
                                    <td class="td text-center">
                                        @if($next && $saldoCukup)
                                            <form method="POST" action="{{ route('invoices.spp-pay') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="santri_id" value="{{ $s->id }}">
                                                <input type="hidden" name="amount" value="{{ $tarif }}">
                                                <button type="submit" class="btn btn-success btn-sm">Bayar</button>
                                            </form>
                                        @elseif($next && !$saldoCukup)
                                            <a href="{{ route('wallet.editTransaction', $s->id) }}" class="btn btn-sm btn-warning">
                                                Saldo Kurang
                                            </a>
                                        @else
                                            <span class="text-ink/40 text-xs">Lunas</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="td text-center py-10 text-ink/50">
                                        Tidak ada santri di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    @elseif($selectedKelas && $santris->isNotEmpty() && ! $hasTariffs)
        <div class="card card-body text-center text-ink/60">
            Belum ada Tarif SPP untuk sekolah ini. Tambahkan tarif terlebih dahulu agar pembayaran SPP dapat diproses.
        </div>
    @elseif($selectedKelas && $santris->isEmpty())
        <div class="card card-body text-center text-ink/60">
            Tidak ada santri di kelas {{ $selectedKelas->nama_kelas }}.
        </div>
    @endif

@endif
@endsection
