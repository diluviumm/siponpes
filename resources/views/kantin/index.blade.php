@extends('layouts.app')

@section('title', $school ? "Kasir Kantin {$school->name}" : 'Kasir Kantin')

@section('page-title', $school ? "Kasir Kantin {$school->name}" : 'Kasir Kantin')

@section('content')

<x-school-filter :schools="$schools" route="kantin.index" :current="$school?->id" />

@if(! $school)

    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Kasir Kantin']" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kasir Kantin</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">Pilih sekolah dari dropdown di atas untuk mencatat pembelian kantin santri.</p>
        </div>
    </div>

    <div class="card card-body text-center text-ink/60">
        Pilih sekolah terlebih dahulu untuk membuka kasir kantin.
    </div>

@else

    @php $isSuper = auth()->user()->isSuperadmin(); @endphp

    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard', $isSuper ? ['school_id' => $school->id] : [])],
        $isSuper ? ['label' => 'Kasir Kantin', 'url' => route('kantin.index')] : 'Kasir Kantin',
        $school->name,
    ]" />

    <div class="page-header">
        <div>
            <h1 class="page-title">Kasir Kantin</h1>
            <x-page-title-rule width="max-w-xs" />
            <p class="page-subtitle">{{ $school->name }} — catat pembelian kantin dari saldo tabungan santri.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('kantin.rekap', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">Rekap Penjualan</a>
            @unless(auth()->user()->isCashier())
                <a href="{{ route('kantin.manage', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-secondary btn-md">Kelola Kantin &amp; Menu</a>
            @endunless
        </div>
    </div>

    <form method="GET" class="mb-6">
        @if($isSuper)
            <input type="hidden" name="school_id" value="{{ $school->id }}">
        @endif
        <div class="flex gap-3 flex-wrap items-center">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau token kartu..." class="input pl-10">
            </div>
            <select name="kelas_id" class="select sm:w-auto" onchange="this.form.submit()" title="Filter kelas">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ (string) $kelasId === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-md flex-shrink-0">Cari</button>
            @if($search || $kelasId)
                <a href="{{ route('kantin.index', $isSuper ? ['school_id' => $school->id] : []) }}" class="btn btn-ghost btn-md flex-shrink-0">Reset</a>
            @endif
        </div>
    </form>

    @if($canteens->isEmpty())
        <div class="card card-body text-center text-ink/60 mb-6">
            Belum ada kantin untuk sekolah ini.
            @unless(auth()->user()->isCashier())
                <a href="{{ route('kantin.manage', $isSuper ? ['school_id' => $school->id] : []) }}" class="text-pine-700 hover:underline font-medium">Buat kantin &amp; menu terlebih dahulu</a>.
            @else
                Hubungi admin sekolah untuk membuat kantin &amp; menu.
            @endunless
        </div>
    @endif

    <div x-data="kasirModal()" x-init="init()">
        <div class="table-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-cream">
                        <tr>
                            <th class="th">NIS</th>
                            <th class="th">Nama</th>
                            <th class="th">Token Kartu</th>
                            <th class="th text-right">Saldo</th>
                            <th class="th text-right">Limit Harian</th>
                            <th class="th">Catat Pembelian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $kasirGroups = $santris
                                ->sortBy(fn ($s) => ($s->kelas?->nama_kelas ?? 'ZZZ'))
                                ->groupBy(fn ($s) => $s->kelas?->nama_kelas ?? 'Tanpa Kelas');
                        @endphp

                        @if($santris->isEmpty())
                            <tr>
                                <td colspan="6" class="td text-center py-10 text-ink/50">
                                    @if($search)
                                        Tidak ada santri dengan kata kunci "<strong>{{ $search }}</strong>".
                                    @else
                                        Belum ada santri di sekolah ini.
                                    @endif
                                </td>
                            </tr>
                        @else
                            @foreach($kasirGroups as $namaKelas => $list)
                                <tr class="bg-pine-50/70">
                                    <td colspan="6" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-pine-800">
                                        Kelas {{ $namaKelas }} · {{ $list->count() }} santri
                                    </td>
                                </tr>
                                @foreach($list as $santri)
                                    <tr class="table-row">
                                        <td class="td font-mono">{{ $santri->nis ?? '-' }}</td>
                                        <td class="td font-medium">{{ $santri->nama }}</td>
                                        <td class="td font-mono text-xs">{{ $santri->santri_card_token ?? '-' }}</td>
                                        <td class="td text-right tabular-nums {{ ($santri->wallet?->balance ?? 0) > 0 ? 'text-pine-700' : 'text-ink/50' }}">
                                            Rp {{ number_format($santri->wallet?->balance ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="td text-right tabular-nums">
                                            {{ $santri->wallet?->daily_spending_limit > 0 ? 'Rp '.number_format($santri->wallet->daily_spending_limit, 2, ',', '.') : '-' }}
                                        </td>
                                        <td class="td">
                                            <button type="button"
                                                    @@click="open({{ $santri->id }}, {{ json_encode($santri->nama) }}, {{ (float) ($santri->wallet?->balance ?? 0) }}, {{ (float) ($santri->wallet?->daily_spending_limit ?? 0) }})"
                                                    class="btn {{ $canteens->isEmpty() ? 'btn-ghost' : 'btn-primary' }} btn-sm"
                                                    {{ $canteens->isEmpty() ? 'disabled' : '' }}>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                Catat Pembelian
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal Catat Pembelian --}}
        <div x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-pine-950/60" @@click="close()"></div>
            <div class="relative min-h-full flex items-center justify-center p-4">
                <div class="relative bg-paper w-full max-w-3xl rounded-xl shadow-xl border border-ink/10">
                    <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-ink/10">
                        <div class="min-w-0">
                            <h3 class="font-display text-lg font-semibold text-pine-900">Catat Pembelian</h3>
                            <p class="text-sm text-ink/60 truncate">
                                <span class="font-medium text-ink" x-text="santriName"></span>
                                · Saldo <span class="tabular-nums font-medium" x-text="fmt(balance)"></span>
                                <span x-show="limit > 0">· Limit <span class="tabular-nums" x-text="fmt(limit)"></span></span>
                            </p>
                        </div>
                        <button type="button" @@click="close()" class="p-1.5 rounded-lg text-ink/50 hover:bg-cream hover:text-ink cursor-pointer shrink-0" aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-5 py-4 max-h-[75vh] overflow-y-auto">
                        <p class="text-[11px] text-ink/60 bg-cream border border-ink/10 rounded-lg px-3 py-2 mb-4">
                            <strong>Format rupiah:</strong> titik = ribuan, koma = sen. Contoh: <code>5.000</code> = lima ribu, <code>599,98</code> = 599 rupiah 98 sen.
                        </p>

                        <form method="POST" action="{{ route('kantin.store') }}" @@submit="syncItems()">
                            @csrf
                            <input type="hidden" name="santri_id" :value="santriId">
                            <input type="hidden" name="amount" x-ref="amount" :value="total">

                            <div>
                                <label class="label">Kantin</label>
                                <select name="canteen_id" x-model="canteenId" @@change="onCanteenChange()" class="select">
                                    <option value="">-- Pilih Kantin --</option>
                                    @foreach($canteens as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <template x-if="canteenId">
                                <div class="space-y-3 mt-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <div class="sm:col-span-1">
                                            <label class="label">Menu Terdata</label>
                                            <div class="relative" @@click.outside="menuListOpen = false">
                                                <input type="text" x-model="menuSearch"
                                                       @@focus="menuListOpen = true"
                                                       @@keydown.enter.prevent="addMenuItem()"
                                                       @@input="clearMenuSelection()"
                                                       :placeholder="pickedName() || 'Cari menu...'"
                                                       class="input">
                                                <div x-show="menuListOpen" x-cloak
                                                     class="absolute z-20 mt-1 w-full bg-paper border border-ink/10 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                                    <template x-for="item in filteredMenus()" :key="item.id">
                                                        <button type="button" @@click="selectMenu(item)"
                                                                class="w-full text-left px-3 py-2 hover:bg-cream text-sm flex items-center justify-between gap-2">
                                                            <span x-text="item.name" class="min-w-0 truncate"></span>
                                                            <span class="text-xs text-ink/50 shrink-0" x-text="fmtPrice(item.price) + ' / ' + item.unit + ' (sisa ' + item.stock + ')'"></span>
                                                        </button>
                                                    </template>
                                                    <p x-show="filteredMenus().length === 0" class="px-3 py-2 text-sm text-ink/50">Tidak ada menu yang cocok.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="label">Jumlah</label>
                                            <input type="number" x-model.number="qty" min="1" step="1" class="input">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" @@click="addMenuItem()" class="btn btn-secondary btn-md w-full">Tambah</button>
                                        </div>
                                    </div>
                                    <p x-show="menuError" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2" x-text="menuError"></p>
                                    <p x-show="stockWarning" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2" x-text="stockWarning"></p>

                                    <div class="border-t border-ink/10 pt-3">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-ink/60 mb-2">Item Lain (di luar menu, mis. Nasi Goreng/Nasi Kuning)</p>
                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                                            <div class="sm:col-span-1 relative">
                                                <input type="text" x-model="adhoc.name" placeholder="Nama item"
                                                       @@input="adhocError = ''"
                                                       @@focus="adhocSuggestOpen = true"
                                                       @@click.outside="adhocSuggestOpen = false"
                                                       @@keydown.enter.prevent="addAdHoc()"
                                                       class="input">
                                                <div x-show="adhocSuggestOpen && adhocSuggestions().length > 0" x-cloak
                                                     class="absolute z-20 mt-1 w-full bg-paper border border-ink/10 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                    <template x-for="(s, i) in adhocSuggestions()" :key="i">
                                                        <button type="button" @@click="pickAdhoc(s)"
                                                                class="w-full text-left px-3 py-2 hover:bg-cream text-sm flex items-center justify-between gap-2">
                                                            <span x-text="s.name" class="min-w-0 truncate"></span>
                                                            <span class="text-xs text-ink/50 shrink-0" x-text="s.price ? fmtPrice(s.price) : ''"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            <div>
                                                <input type="number" x-model.number="adhoc.qty" min="1" step="1" placeholder="Jumlah" @@input="adhocError = ''" class="input">
                                            </div>
                                            <div>
                                                <input type="text" x-model="adhoc.price" inputmode="numeric" placeholder="Harga satuan" @@input="adhoc.price = fmtPriceInput(adhoc.price); adhocError = ''" class="input">
                                                <p class="text-[11px] text-ink/50 mt-1" x-show="parsePrice(adhoc.price) > 0">
                                                    = <span x-text="fmt(parsePrice(adhoc.price))"></span>
                                                    <span x-show="adhoc.qty > 1"> × <span x-text="adhoc.qty"></span> = <span x-text="fmt(parsePrice(adhoc.price) * Number(adhoc.qty))"></span></span>
                                                </p>
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" @@click="addAdHoc()" class="btn btn-secondary btn-md w-full">+ Item</button>
                                            </div>
                                        </div>
                                        <p x-show="adhocError" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2 mt-2" x-text="adhocError"></p>
                                        <p class="text-[11px] text-ink/50 mt-2">Titik ribuan otomatis. Contoh: ketik <code>5000</code> → <strong>5.000</strong> = Rp 5.000 (lima ribu).</p>
                                    </div>
                                </div>
                            </template>

                            <div x-show="cart.length > 0" class="mt-4 border border-ink/10 rounded-lg bg-paper">
                                <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-ink/60 border-b border-ink/10">Keranjang</div>
                                <div class="divide-y divide-ink/10">
                                    <template x-for="(it, idx) in cart" :key="idx">
                                        <div class="flex flex-wrap items-center gap-2 px-3 py-2">
                                            <template x-if="it.id">
                                                <span class="flex-1 min-w-0 truncate text-sm font-medium" x-text="it.name"></span>
                                            </template>
                                            <template x-if="!it.id">
                                                <input type="text" x-model="it.name" placeholder="Nama item" class="input-sm text-sm flex-1 min-w-32">
                                            </template>
                                            <template x-if="!it.id">
                                                <input type="number" x-model.number="it.price" min="0" step="100" placeholder="Harga" class="input-sm text-sm w-24 text-right">
                                            </template>
                                            <template x-if="it.id">
                                                <span class="text-sm tabular-nums text-ink/70 w-20 text-right shrink-0" x-text="fmtPrice(it.price)"></span>
                                            </template>
                                            <input type="number" x-model.number="it.qty" min="1" step="1" @@input="capQty(it)" class="input-sm w-20 text-sm">
                                            <span class="text-sm tabular-nums text-ink/70 w-24 text-right shrink-0" x-text="fmt(it.qty * it.price)"></span>
                                            <button type="button" @@click="remove(idx)" class="text-rose-600 hover:text-rose-800 p-1 shrink-0" title="Hapus item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                            <input type="hidden" :name="'items['+idx+'][name]'" :value="it.name">
                                            <input type="hidden" :name="'items['+idx+'][unit_price]'" :value="it.price">
                                            <input type="hidden" :name="'items['+idx+'][quantity]'" :value="it.qty">
                                            <input type="hidden" :name="'items['+idx+'][unit]'" :value="it.unit">
                                            <input type="hidden" :name="'items['+idx+'][canteen_item_id]'" :value="it.id || ''">
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between px-3 py-2 bg-cream rounded-b-lg font-semibold">
                                    <span>Total</span>
                                    <span class="tabular-nums text-pine-700" x-text="fmt(total)"></span>
                                </div>
                            </div>

                            <div class="mt-3 text-sm space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-ink/60">Saldo tersedia</span>
                                    <span class="tabular-nums font-medium" :class="paymentMethod === 'wallet' && total > balance ? 'text-rose-700' : 'text-pine-700'" x-text="fmt(balance)"></span>
                                </div>
                                <div class="flex justify-between" x-show="limit > 0">
                                    <span class="text-ink/60">Limit harian</span>
                                    <span class="tabular-nums font-medium text-ink/70" x-text="fmt(limit)"></span>
                                </div>
                            </div>

                            <div class="mt-3 space-y-2">
                                <p x-show="insufficient" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2">
                                    Saldo tidak cukup untuk total ini. <strong>Top-up saldo</strong> santri atau pilih metode
                                    <strong>Tunai (di luar saldo)</strong>.
                                </p>
                                <p x-show="limitExceeded && !insufficient" class="text-sm text-amber-800 bg-gold-50 border border-gold-300 rounded-lg px-3 py-2">
                                    Total melebihi limit harian (Rp <span x-text="fmt(limit)"></span>). Naikkan limit atau gunakan metode
                                    <strong>Tunai (di luar saldo)</strong>.
                                </p>
                            </div>

                            <div class="mt-3">
                                <label class="label">Metode Bayar</label>
                                <div class="flex flex-wrap gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="radio" name="payment_method" value="wallet" x-model="paymentMethod" class="accent-pine-700">
                                        Potong Saldo (Wallet)
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="accent-pine-700">
                                        Tunai (di luar saldo)
                                    </label>
                                </div>
                                <p class="text-xs text-ink/50 mt-1" x-show="paymentMethod === 'cash'">Tunai tidak memotong saldo & tidak dihitung dalam batas pengeluaran harian.</p>
                            </div>

                            <div class="flex gap-2 pt-4 border-t border-ink/10 mt-4">
                                <button type="submit" class="btn btn-primary btn-md flex-1 justify-center" :disabled="!canPay">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Bayar
                                </button>
                                <button type="button" @@click="close()" class="btn btn-ghost btn-md">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endif

<script>
    @php
        $menuJson = json_encode(
            $menuItems->map(fn ($items) => $items->map(fn ($it) => [
                'id' => $it->id,
                'name' => $it->name,
                'price' => (float) $it->price,
                'unit' => $it->unit,
                'stock' => $it->stock,
            ]))->toArray(),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        $santrisJson = $santris->mapWithKeys(fn ($s) => [
            $s->id => [
                'nama' => $s->nama,
                'balance' => (float) ($s->wallet?->balance ?? 0),
                'limit' => (float) ($s->wallet?->daily_spending_limit ?? 0),
            ],
        ])->all();
    @endphp
    window.__canteenMenu = {!! $menuJson !!};
    window.__kasirSantris = @json($santrisJson);

    window.__kasirFailed = @json(session()->has('error'));

    @if(! session()->has('error'))
        localStorage.removeItem('siponpes.kasirCart');
    @endif

    window.kasirModal = function () {
        return {
            show: false,
            santriId: null,
            santriName: '',
            balance: 0,
            limit: 0,
            canteenId: '',
            paymentMethod: 'wallet',
            pickedItemId: '',
            qty: 1,
            menuSearch: '',
            menuListOpen: false,
            menuError: '',
            adhoc: { name: '', qty: 1, price: '' },
            adhocSuggestOpen: false,
            adhocError: '',
            cart: [],
            stockWarning: '',
            adhocHistory: [],
            init() {
                this.adhocHistory = this.loadAdhocHistory();

                if (window.__kasirFailed) {
                    const saved = this.loadSavedCart();

                    if (saved && window.__kasirSantris[saved.santriId]) {
                        const info = window.__kasirSantris[saved.santriId];
                        this.santriId = saved.santriId;
                        this.santriName = info.nama;
                        this.balance = info.balance;
                        this.limit = info.limit;
                        this.cart = saved.cart || [];
                        this.canteenId = saved.canteenId || '';
                        this.paymentMethod = saved.paymentMethod || 'wallet';
                        this.show = true;
                    }
                }
            },
            open(santriId, santriName, balance, limit) {
                this.santriId = santriId;
                this.santriName = santriName;
                this.balance = balance;
                this.limit = limit;
                this.canteenId = '';
                this.paymentMethod = 'wallet';
                this.pickedItemId = '';
                this.qty = 1;
                this.menuSearch = '';
                this.menuListOpen = false;
                this.menuError = '';
                this.adhoc = { name: '', qty: 1, price: '' };
                this.adhocSuggestOpen = false;
                this.adhocError = '';
                this.cart = [];
                this.stockWarning = '';
                this.show = true;
            },
            close() {
                this.show = false;
            },
            onCanteenChange() {
                this.cart = [];
                this.pickedItemId = '';
                this.qty = 1;
                this.menuSearch = '';
                this.menuListOpen = false;
                this.menuError = '';
                this.stockWarning = '';
                this.adhoc = { name: '', qty: 1, price: '' };
                this.adhocError = '';
            },
            get total() {
                return this.cart.reduce((sum, it) => sum + (it.qty * it.price), 0);
            },
            get insufficient() {
                return this.paymentMethod === 'wallet' && this.total > this.balance;
            },
            get limitExceeded() {
                return this.paymentMethod === 'wallet' && this.limit > 0 && this.total > this.limit;
            },
            get canPay() {
                return this.total > 0 && !!this.canteenId && !this.stockWarning && !this.insufficient && !this.limitExceeded;
            },
            fmt(n) {
                return 'Rp ' + Number(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            fmtPrice(n) {
                return 'Rp ' + Number(n).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            },
            fmtPriceInput(v) {
                const parts = String(v == null ? '' : v).split(',');
                let int = parts[0].replace(/\D/g, '');
                int = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                if (parts.length > 1) {
                    const dec = parts[1].replace(/\D/g, '').slice(0, 2);

                    return dec ? int + ',' + dec : int;
                }

                return int;
            },
            parsePrice(v) {
                return Number(String(v).replace(/\./g, '').replace(',', '.'));
            },
            menuForCanteen() {
                return window.__canteenMenu[this.canteenId] || [];
            },
            filteredMenus() {
                const q = (this.menuSearch || '').trim().toLowerCase();

                if (!q) {
                    return this.menuForCanteen();
                }

                return this.menuForCanteen().filter(i => i.name.toLowerCase().includes(q));
            },
            pickedName() {
                if (!this.pickedItemId) return '';
                const item = this.findItem(this.pickedItemId);
                return item ? item.name : '';
            },
            findItem(id) {
                const items = this.menuForCanteen();
                return items.find(i => String(i.id) === String(id));
            },
            selectMenu(item) {
                this.pickedItemId = item.id;
                this.menuSearch = item.name;
                this.menuListOpen = false;
                this.menuError = '';
            },
            clearMenuSelection() {
                if (this.menuSearch !== this.pickedName()) {
                    this.pickedItemId = '';
                }
            },
            addMenuItem() {
                if (!this.pickedItemId) {
                    this.menuError = 'Pilih menu terlebih dahulu.';
                    return;
                }

                const item = this.findItem(this.pickedItemId);

                if (!item) {
                    this.menuError = 'Menu tidak ditemukan.';
                    return;
                }

                if (!this.qty || this.qty < 1) {
                    this.menuError = 'Jumlah minimal 1.';
                    return;
                }

                this.menuError = '';

                const qty = Number(this.qty || 1);
                const inCart = this.cart.filter(c => c.id === item.id).reduce((s, c) => s + (c.qty || 0), 0);
                const remaining = item.stock - inCart;

                if (qty > remaining) {
                    this.stockWarning = "Stok '" + item.name + "' tidak mencukupi. Sisa stok: " + Math.max(0, remaining) + '.';
                    return;
                }

                this.stockWarning = '';

                const existing = this.cart.find(c => c.id === item.id);

                if (existing) {
                    existing.qty += qty;
                } else {
                    this.cart.push({
                        id: item.id,
                        name: item.name,
                        unit: item.unit,
                        price: Number(item.price),
                        qty: qty,
                    });
                }

                this.pickedItemId = '';
                this.menuSearch = '';
                this.qty = 1;
            },
            capQty(it) {
                if (!it.id) return;

                const item = this.findItem(it.id);

                if (!item) return;

                const others = this.cart
                    .filter(c => c.id === it.id && c !== it)
                    .reduce((s, c) => s + (c.qty || 0), 0);
                const max = Math.max(0, item.stock - others);

                if (it.qty > max) {
                    it.qty = max;
                    this.stockWarning = "Stok '" + it.name + "' hanya sisa " + max + '.';
                } else {
                    this.stockWarning = '';
                }
            },
            adhocSuggestions() {
                const q = (this.adhoc.name || '').trim().toLowerCase();

                return this.adhocHistory
                    .filter(h => q === '' || h.name.toLowerCase().includes(q))
                    .slice(0, 8);
            },
            pickAdhoc(s) {
                this.adhoc = { name: s.name, qty: s.qty || 1, price: this.fmtPriceInput(String(s.price || 0)) };
                this.adhocSuggestOpen = false;
                this.adhocError = '';
            },
            addAdHoc() {
                if (!this.adhoc.name.trim()) {
                    this.adhocError = 'Nama item wajib diisi.';
                    return;
                }

                if (!this.adhoc.qty || this.adhoc.qty < 1) {
                    this.adhocError = 'Jumlah wajib diisi (minimal 1).';
                    return;
                }

                const price = this.parsePrice(this.adhoc.price);

                if (!price || price <= 0) {
                    this.adhocError = 'Harga wajib diisi (lebih dari 0).';
                    return;
                }

                this.adhocError = '';

                this.cart.push({
                    id: null,
                    name: this.adhoc.name.trim(),
                    unit: 'pcs',
                    price: price,
                    qty: Number(this.adhoc.qty || 1),
                });

                this.saveAdhocHistory(this.adhoc.name.trim(), price, Number(this.adhoc.qty || 1));
                this.adhoc = { name: '', qty: 1, price: '' };
            },
            loadAdhocHistory() {
                try {
                    const raw = JSON.parse(localStorage.getItem('siponpes.adhocHistory') || '[]');

                    return Array.isArray(raw) ? raw : [];
                } catch (e) {
                    return [];
                }
            },
            saveAdhocHistory(name, price, qty) {
                const next = this.adhocHistory.filter(h => h.name.toLowerCase() !== name.toLowerCase());
                next.unshift({ name: name, price: price, qty: qty });
                this.adhocHistory = next.slice(0, 20);
                localStorage.setItem('siponpes.adhocHistory', JSON.stringify(this.adhocHistory));
            },
            remove(idx) {
                this.cart.splice(idx, 1);
            },
            syncItems() {
                if (this.$refs.amount) {
                    this.$refs.amount.value = this.total;
                }

                localStorage.setItem('siponpes.kasirCart', JSON.stringify({
                    santriId: this.santriId,
                    canteenId: this.canteenId,
                    paymentMethod: this.paymentMethod,
                    cart: this.cart,
                }));
            },
            loadSavedCart() {
                try {
                    return JSON.parse(localStorage.getItem('siponpes.kasirCart') || 'null');
                } catch (e) {
                    return null;
                }
            },
        };
    };
</script>

@endsection
