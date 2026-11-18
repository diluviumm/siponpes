@extends('layouts.app')

@section('title', "Kelola {$canteen->name}")

@section('page-title', "Kelola {$canteen->name}")

@section('content')

@php
    $isSuper = auth()->user()->isSuperadmin();
    $units = \App\Support\CanteenUnits::all();
@endphp

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
    ['label' => 'Kasir Kantin', 'url' => route('kantin.index', $isSuper ? ['school_id' => $school->id] : [])],
    ['label' => 'Kelola Kantin & Menu', 'url' => route('kantin.manage', $isSuper ? ['school_id' => $school->id] : [])],
    $canteen->name,
]" />

<div class="page-header">
    <div>
        <h1 class="page-title">Kelola {{ $canteen->name }}</h1>
        <x-page-title-rule width="max-w-xs" />
        <p class="page-subtitle">{{ $school->name }} — kelola daftar menu terdata.</p>
    </div>
    <div class="page-actions flex gap-2">
        <a href="{{ route('kantin.manage', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md">Kembali</a>
        <a href="{{ route('kantin.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">Buka Kasir</a>
        <form action="{{ route('kantin.canteen.destroy', $canteen) }}" method="POST"
              data-confirm="Hapus kantin {{ $canteen->name }} beserta seluruh menunya? Riwayat transaksi tetap tersimpan."
              data-confirm-label="Hapus">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-md">Hapus Kantin</button>
        </form>
    </div>
</div>

<div class="card card-body mb-6">
    @if($canteen->items->isNotEmpty())
        <div class="mb-2 flex items-center justify-between">
            <p class="text-sm text-ink/60">Harga otomatis diberi tanda titik (contoh: <code>5000</code> → tampil Rp 5.000). Stok ditulis angka bulat tanpa titik/koma.</p>
            <p class="text-xs text-ink/50">Menu dengan stok 0 otomatis menjadi <strong>Habis</strong> &amp; tidak muncul di kasir.</p>
        </div>
        <div class="table-card mb-4">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="th">Menu</th>
                            <th class="th text-right">Harga</th>
                            <th class="th">Satuan</th>
                            <th class="th text-right">Stok</th>
                            <th class="th text-center">Status</th>
                            <th class="th text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($canteen->items as $item)
                            <tr class="table-row align-middle">
                                <td class="td">
                                    <input form="update-item-{{ $item->id }}" type="text" name="name" value="{{ $item->name }}" class="input-sm text-sm w-full min-w-40" required maxlength="255">
                                </td>
                                <td class="td">
                                    <input form="update-item-{{ $item->id }}" type="text" inputmode="numeric" name="price" value="{{ $item->price }}" class="input-sm text-sm w-28 text-right rupiah-input" required>
                                </td>
                                <td class="td">
                                    <select form="update-item-{{ $item->id }}" name="unit" class="select-sm text-sm w-28">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit }}" {{ $item->unit === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="td">
                                    <input form="update-item-{{ $item->id }}" type="number" name="stock" value="{{ $item->stock }}" min="0" step="1" class="input-sm text-sm w-24 text-right" required>
                                </td>
                                <td class="td text-center">
                                    @if(($item->stock ?? 0) > 0)
                                        <span class="badge badge-green">Tersedia</span>
                                    @else
                                        <span class="badge badge-red">Habis</span>
                                    @endif
                                </td>
                                <td class="td text-center whitespace-nowrap">
                                    <form id="update-item-{{ $item->id }}" method="POST" action="{{ route('kantin.item.update', $item) }}" class="hidden">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <div class="inline-flex items-center gap-2">
                                        <button type="submit" form="update-item-{{ $item->id }}" class="btn btn-warning btn-sm">Simpan</button>
                                        <form action="{{ route('kantin.item.destroy', $item) }}" method="POST" class="inline"
                                              data-confirm="Hapus menu {{ $item->name }}? Riwayat transaksi lama tetap tersimpan."
                                              data-confirm-label="Hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="text-sm text-ink/60 mb-4">Belum ada menu. Tambahkan menu terdata di bawah.</p>
    @endif

    <form method="POST" action="{{ route('kantin.item.store') }}" class="bg-cream border border-ink/10 rounded-lg p-4">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $school->id }}">
        <input type="hidden" name="canteen_id" value="{{ $canteen->id }}">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink/60 mb-2">Tambah Menu — {{ $canteen->name }}</p>
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
            <input type="text" name="name" placeholder="Nama menu (mis. Nasi + Lauk)" class="input" required maxlength="255">
            <input type="text" inputmode="numeric" name="price" placeholder="Harga satuan" class="input rupiah-input" required>
            <select name="unit" class="select">
                @foreach($units as $unit)
                    <option value="{{ $unit }}" {{ $unit === 'pcs' ? 'selected' : '' }}>{{ $unit }}</option>
                @endforeach
            </select>
            <input type="number" name="stock" placeholder="Stok" min="0" step="1" class="input" required>
            <button type="submit" class="btn btn-primary btn-md">Tambah Menu</button>
        </div>
    </form>
</div>

<script>
    (function () {
        var KEY = 'siponpes.manageScroll';

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                sessionStorage.setItem(KEY, String(window.scrollY));
            });
        });

        var saved = sessionStorage.getItem(KEY);

        if (saved !== null) {
            sessionStorage.removeItem(KEY);
            window.addEventListener('load', function () {
                window.scrollTo(0, parseInt(saved, 10) || 0);
            });
        }
    })();
</script>
@endsection
