<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', trim(\Illuminate\Support\Facades\View::yieldContent('page-title')) ?: 'SIPONPES')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream text-ink antialiased">

<div x-data="sidebar()" class="flex min-h-screen">

    {{-- Sidebar + handle (wrapper sticky agar tombol selalu nempel di tepi sidebar) --}}
    <div class="sticky top-0 self-start h-screen relative z-30 shrink-0">
        <aside x-cloak
               class="h-full bg-gradient-to-b from-pine-900 to-pine-950 text-white transform transition-all duration-300 flex flex-col overflow-hidden"
               :class="[open ? 'w-64' : 'w-20 sidebar-collapsed']">

        {{-- Brand --}}
        <div class="sidebar-brand flex flex-col px-6 pt-6 pb-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-pine-950/40 ring-1 ring-gold-300/40 shrink-0">
                    <svg class="w-6 h-6 text-pine-950" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="font-display text-xl font-semibold tracking-wide leading-tight whitespace-nowrap">SIPONPES</h1>
                    <p class="text-[11px] text-pine-200/80 leading-tight whitespace-nowrap">Sistem Informasi<br>Pondok Pesantren</p>
                </div>
            </div>

            {{-- Motif signature: garis + berlian emas --}}
            <div class="sidebar-brand-motif mt-4 flex items-center gap-2 text-gold-500/60" aria-hidden="true">
                <span class="h-px flex-1 bg-gradient-to-r from-transparent to-gold-500/50"></span>
                <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                <span class="h-px flex-1 bg-gradient-to-l from-transparent to-gold-500/50"></span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-scroll mt-1 flex-1 overflow-y-auto px-3 pb-4" aria-label="Navigasi utama">

            @php
                $isCashier = auth()->user()->isCashier();
                $pendingSchoolsCount = auth()->user()->isSuperadmin()
                    ? \App\Models\School::where('status', \App\Models\School::STATUS_PENDING)->count()
                    : 0;
                $overdueInvoicesCount = (! auth()->user()->isSuperadmin() && ! $isCashier)
                    ? \App\Models\Invoice::where('status', \App\Models\Invoice::STATUS_UNPAID)->whereDate('due_date', '<=', now())->count()
                    : 0;
            @endphp

            <p class="sidebar-section">
                <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                Menu Utama
            </p>

            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('dashboard'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            @if(auth()->user()->isSuperadmin())
                <a href="{{ route('schools.index') }}"
                   class="sidebar-link {{ request()->routeIs('schools.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                    @if(request()->routeIs('schools.*'))
                        <span class="sidebar-active-bar"></span>
                    @endif
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Data Sekolah
                    @if($pendingSchoolsCount > 0)
                        <span class="ml-auto text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gold-500 text-pine-950">{{ $pendingSchoolsCount }}</span>
                    @endif
                </a>
            @endif

            @unless($isCashier)

            <a href="{{ route('kelas.index') }}"
               class="sidebar-link {{ request()->routeIs('kelas.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('kelas.*'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Data Kelas
            </a>

            <a href="{{ route('santri.index') }}"
               class="sidebar-link {{ request()->routeIs('santri.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('santri.*'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                Data Santri
            </a>

            <a href="{{ route('wallet.index') }}"
               class="sidebar-link {{ request()->routeIs('wallet.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('wallet.*'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Tabungan Santri
            </a>

            <a href="{{ route('spp_tariffs.index') }}"
               class="sidebar-link {{ request()->routeIs('spp_tariffs.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('spp_tariffs.*'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tarif SPP
            </a>

            <a href="{{ route('invoices.spp-payment') }}"
               class="sidebar-link {{ request()->routeIs('invoices.spp-payment') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('invoices.spp-payment'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pembayaran SPP
            </a>

            <a href="{{ route('invoices.index') }}"
               class="sidebar-link {{ request()->routeIs('invoices.*') && ! request()->routeIs('invoices.spp-payment') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('invoices.*') && ! request()->routeIs('invoices.spp-payment'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Riwayat Invoice
                @if($overdueInvoicesCount > 0)
                    <span class="ml-auto text-[11px] font-semibold px-2 py-0.5 rounded-full bg-rose-600 text-white">{{ $overdueInvoicesCount }}</span>
                @endif
            </a>

            <a href="{{ route('kas.index') }}"
               class="sidebar-link {{ request()->routeIs('kas.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('kas.*'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Kas Sekolah
            </a>

            @endunless

            <a href="{{ route('kantin.index') }}"
               class="sidebar-link {{ request()->routeIs('kantin.*') && ! request()->routeIs('kantin.rekap') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('kantin.*') && ! request()->routeIs('kantin.rekap'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Kasir Kantin
            </a>

            @if($isCashier)
                <a href="{{ route('kantin.rekap') }}"
                   class="sidebar-link {{ request()->routeIs('kantin.rekap') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                    @if(request()->routeIs('kantin.rekap'))
                        <span class="sidebar-active-bar"></span>
                    @endif
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Rekap Penjualan
                </a>
            @endif

            @unless($isCashier)
            <p class="sidebar-section">
                <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                Laporan Keuangan
            </p>

            <a href="{{ route('financial.journal') }}"
               class="sidebar-link {{ request()->routeIs('financial.journal') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.journal'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Jurnal Umum
            </a>

            <a href="{{ route('financial.ledger') }}"
               class="sidebar-link {{ request()->routeIs('financial.ledger') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.ledger'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                Buku Besar
            </a>

            <a href="{{ route('financial.trial_balance') }}"
               class="sidebar-link {{ request()->routeIs('financial.trial_balance') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.trial_balance'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Neraca Saldo
            </a>

            <a href="{{ route('financial.balance_sheet') }}"
               class="sidebar-link {{ request()->routeIs('financial.balance_sheet') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.balance_sheet'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                Neraca
            </a>

            <a href="{{ route('financial.income_statement') }}"
               class="sidebar-link {{ request()->routeIs('financial.income_statement') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.income_statement'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Surplus/Defisit
            </a>

            <a href="{{ route('financial.cash_flow') }}"
               class="sidebar-link {{ request()->routeIs('financial.cash_flow') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.cash_flow'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Arus Kas
            </a>

            <a href="{{ route('financial.adjusting') }}"
               class="sidebar-link {{ request()->routeIs('financial.adjusting') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('financial.adjusting'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Jurnal Penyesuaian
            </a>

            <p class="sidebar-section">
                <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                Pengaturan
            </p>

            <a href="{{ route('staff.index') }}"
               class="sidebar-link {{ request()->routeIs('staff.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                @if(request()->routeIs('staff.*'))
                    <span class="sidebar-active-bar"></span>
                @endif
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Kelola Akun
            </a>

            @if(! auth()->user()->isSuperadmin())
                <a href="{{ route('school.profile') }}"
                   class="sidebar-link {{ request()->routeIs('school.profile') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                    @if(request()->routeIs('school.profile'))
                        <span class="sidebar-active-bar"></span>
                    @endif
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Profil Sekolah
                </a>
            @endif

            @if(auth()->user()->isSuperadmin())
                <a href="{{ route('accounts.index') }}"
                   class="sidebar-link {{ request()->routeIs('accounts.*') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                    @if(request()->routeIs('accounts.*'))
                        <span class="sidebar-active-bar"></span>
                    @endif
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h18M3 18h18"/></svg>
                    Kode Akun
                </a>
            @endif

            @if(auth()->user()->isSuperadmin() || ! auth()->user()->isCashier())
                <a href="{{ route('audit.index') }}"
                   class="sidebar-link {{ request()->routeIs('audit.index') ? 'sidebar-link-active' : 'text-pine-100/80 hover:bg-pine-800/70 hover:text-white' }}">
                    @if(request()->routeIs('audit.index'))
                        <span class="sidebar-active-bar"></span>
                    @endif
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a2 2 0 10-2-2m0 0v6m0-6a2 2 0 112 2m-2 0h2m2 5h-8a2 2 0 01-2-2V8a2 2 0 012-2h1M15 5a2 2 0 012 2m-2-4a4 4 0 014 4v2a4 4 0 01-4 4m0-6h4"/></svg>
                    Audit Trail
                </a>
            @endif
            @endunless
        </nav>
    </aside>

    {{-- Handle buka/tutup sidebar (semua ukuran layar) --}}
    <button type="button" @@click="toggleHandle()" x-cloak
            class="absolute right-0 top-1/2 z-40 w-8 h-8 -translate-y-1/2 translate-x-0 items-center justify-center rounded-full bg-pine-900 text-gold-300 shadow-lg shadow-pine-950/40 border border-white/10 hover:bg-pine-800 transition-all duration-300 cursor-pointer flex"
            :title="open ? 'Sempitkan sidebar' : 'Lebarkan sidebar'">
        <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        <svg x-show="!open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
    </button>
    </div>

    {{-- Content --}}
    <main class="flex-1 flex flex-col min-w-0">

        <header class="bg-paper/95 backdrop-blur-sm border-b border-ink/10 sticky top-0 z-20">
            <div class="px-4 lg:px-8 h-16 flex items-center gap-4">
                <div class="min-w-0">
                    <h1 class="font-display text-lg lg:text-xl font-semibold text-pine-900 truncate">
                        @yield('page-title', 'SIPONPES')
                    </h1>
                    @if(! auth()->user()->isSuperadmin() && auth()->user()->school)
                        <p class="text-[11px] text-ink/50 leading-tight truncate">
                            {{ auth()->user()->school->name }}
                        </p>
                    @endif
                </div>

                <div class="ml-auto flex items-center gap-3">

                    @if(request()->routeIs('dashboard', 'santri.*', 'kelas.*', 'wallet.*', 'invoices.*', 'spp_tariffs.*', 'kantin.*'))
                    @unless(auth()->user()->isCashier())
                    <div x-data="globalSearch()" class="relative hidden md:block">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink/40 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input x-ref="input" type="text" x-model="query" @@input="search()" @@click="open = true"
                                   placeholder="Cari santri..."
                                   class="w-40 md:w-56 xl:w-80 pl-9 pr-12 py-1.5 text-sm rounded-lg bg-cream/70 border border-ink/10 focus:outline-none focus:bg-paper focus:border-pine-400 transition-colors"
                                   @@keydown.escape="open = false">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-medium text-ink/40 border border-ink/20 rounded px-1.5 py-0.5">Ctrl&nbsp;K</span>
                        </div>
                        <div x-show="open" x-cloak @@click.outside="open = false"
                             class="absolute right-0 top-full mt-2 w-80 max-w-[85vw] z-40 bg-paper shadow-lg rounded-lg overflow-hidden">
                            <div class="max-h-80 overflow-y-auto">
                                <p x-show="loading" class="px-3 py-4 text-sm text-ink/50 text-center">Mencari...</p>
                                <template x-for="r in results" :key="r.id">
                                    <a :href="r.url" @@click="open = false"
                                       class="flex items-center justify-between gap-3 px-3 py-2.5 hover:bg-cream transition-colors">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-ink truncate" x-text="r.nama"></span>
                                            <span class="block text-xs text-ink/50 truncate" x-text="(r.nis ? r.nis + ' · ' : '') + (r.kelas || '-') + (r.sekolah ? ' · ' + r.sekolah : '')"></span>
                                        </span>
                                        <svg class="w-4 h-4 text-pine-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </template>
                                <p x-show="!loading && query.trim().length < 2" class="px-3 py-4 text-sm text-ink/50 text-center">Ketik minimal 2 huruf untuk mencari santri.</p>
                                <p x-show="!loading && query.trim().length >= 2 && results.length === 0" class="px-3 py-4 text-sm text-ink/50 text-center">Tidak ditemukan santri dengan kata kunci tersebut.</p>
                            </div>
                        </div>
                    </div>
                    @endunless
                    @endif

                    <div class="hidden lg:flex items-center gap-2 text-sm text-ink/60 min-w-0">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="truncate max-w-[10rem] xl:max-w-none">{{ \Illuminate\Support\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open"
                                class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-cream transition-colors cursor-pointer">
                            <span class="w-9 h-9 rounded-full bg-pine-700 text-gold-300 flex items-center justify-center font-semibold text-sm shrink-0">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden lg:block text-left min-w-0">
                                <span class="block text-sm font-medium text-ink truncate max-w-[10rem]">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-ink/50 truncate max-w-[10rem]">{{ auth()->user()->email }}</span>
                            </span>
                            <svg class="w-4 h-4 text-ink/50 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak @@click.outside="open = false"
                             class="absolute right-0 mt-2 w-64 z-30 rounded-lg bg-paper border border-ink/10 shadow-lg p-1.5">
                            <div class="px-3 py-2.5 border-b border-ink/10 mb-1">
                                <p class="font-semibold text-sm text-ink truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-ink/50 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-ink/80 hover:bg-cream rounded-md">
                                <svg class="w-4 h-4 text-pine-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Profil Saya
                            </a>
                            <a href="{{ route('password.change') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-ink/80 hover:bg-cream rounded-md">
                                <svg class="w-4 h-4 text-pine-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Ganti Password
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-rose-700 hover:bg-rose-50 rounded-md cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="flex-1 p-4 lg:p-8 max-w-7xl mx-auto w-full">

            @yield('content')
        </section>

        <footer class="px-4 lg:px-8 py-4 text-xs text-ink/40 text-center">
            SIPONPES — Sistem Informasi Pondok Pesantren
            <span class="mx-2">·</span>
            <a href="{{ route('privacy') }}" class="hover:underline">Kebijakan Privasi</a>
            <span class="mx-2">·</span>
            <a href="{{ route('terms') }}" class="hover:underline">Syarat Layanan</a>
        </footer>

    </main>

</div>

{{-- Modal konfirmasi custom SIPONPES --}}
<div x-data="confirmDialog()" x-show="open" x-cloak>
    <div class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="fixed inset-0 bg-pine-950/60" @@click="cancel()"></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="relative bg-paper w-full max-w-md rounded-xl shadow-xl border border-ink/10 overflow-hidden"
                 @@keydown.escape.window="cancel()">
                <div class="px-5 py-4 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-gold-100 text-gold-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="confirm-title" class="font-display text-lg font-semibold text-pine-900" x-text="title"></h2>
                        <p class="text-sm text-ink/70 mt-1 whitespace-pre-line" x-text="message"></p>
                    </div>
                </div>
                <div class="px-5 py-4 flex gap-2 justify-end bg-cream/60 border-t border-ink/10">
                    <button type="button" @@click="cancel()" class="btn btn-ghost btn-md">Batal</button>
                    <button type="button" @@click="accept()" class="btn btn-danger btn-md" x-text="confirmLabel"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-data="toast()" class="fixed bottom-5 right-5 z-50 space-y-2 w-80 max-w-[90vw]">
    <template x-for="t in items" :key="t.id">
        <div class="rounded-lg shadow-lg px-4 py-3 text-sm font-medium border flex items-start gap-3"
             :class="{
                 'bg-rose-700 text-white border-rose-800': t.type === 'error',
                 'bg-gold-600 text-pine-950 border-gold-700': t.type === 'warning',
                 'bg-blue-700 text-white border-blue-800': t.type === 'info',
                 'bg-pine-700 text-white border-pine-800': t.type === 'success'
             }">
            <span class="flex-1" x-text="t.message"></span>
            <button type="button" @@click="remove(t.id)" class="shrink-0 opacity-80 hover:opacity-100 cursor-pointer" aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

<script>
    window.sidebar = function () {
        const OPEN_KEY = 'siponpes.sidebarOpen';

        const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

        return {
            open: isDesktop()
                ? (localStorage.getItem(OPEN_KEY) === null ? true : localStorage.getItem(OPEN_KEY) === '1')
                : false,
            toggleHandle() {
                this.open = !this.open;
                localStorage.setItem(OPEN_KEY, this.open ? '1' : '0');
            },
        };
    };

    window.confirmDialog = function () {
        return {
            open: false,
            title: 'Konfirmasi',
            message: '',
            confirmLabel: 'Ya, Lanjutkan',
            resolve: null,
            init() {
                window.__siponpesConfirm = (message, options) => this.show(message, options);
            },
            show(message, options = {}) {
                this.title = options.title || 'Konfirmasi';
                this.message = message || '';
                this.confirmLabel = options.confirmLabel || 'Ya, Lanjutkan';
                this.open = true;

                return new Promise((resolve) => {
                    this.resolve = resolve;
                });
            },
            accept() {
                this.open = false;
                if (this.resolve) {
                    this.resolve(true);
                    this.resolve = null;
                }
            },
            cancel() {
                this.open = false;
                if (this.resolve) {
                    this.resolve(false);
                    this.resolve = null;
                }
            },
        };
    };

    window.toast = function () {
        return {
            items: [],
            init() {
                const messages = {
                    error: @json(session('error')),
                    success: @json(session('success')),
                    warning: @json(session('warning')),
                    info: @json(session('info')),
                };

                for (const type of ['error', 'warning', 'info', 'success']) {
                    if (messages[type]) {
                        this.push(type, messages[type]);
                    }
                }

                window.__siponpesToast = (type, message) => this.push(type, message);
            },
            push(type, message) {
                const id = Date.now() + Math.random();
                this.items.push({ id: id, type: type, message: message });
                setTimeout(() => this.remove(id), type === 'error' ? 8000 : 5000);
            },
            remove(id) {
                this.items = this.items.filter((t) => t.id !== id);
            },
        };
    };

    window.globalSearch = function () {
        return {
            open: false,
            query: '',
            results: [],
            loading: false,
            timer: null,
            init() {
                document.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                        e.preventDefault();
                        this.open = true;
                        this.$nextTick(() => this.$refs.input?.focus());
                    }
                    if (e.key === 'Escape') {
                        this.open = false;
                    }
                });
            },
            search() {
                clearTimeout(this.timer);
                const q = this.query.trim();

                if (q.length < 2) {
                    this.results = [];
                    return;
                }

                this.loading = true;
                this.timer = setTimeout(async () => {
                    try {
                        const res = await fetch('{{ route('santri.search') }}?q=' + encodeURIComponent(q), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        });
                        this.results = await res.json();
                    } catch (e) {
                        this.results = [];
                    }
                    this.loading = false;
                }, 250);
            },
        };
    };
</script>

{{-- Jaring pengaman aksesibilitas: beri nama pada kontrol yang belum punya label. --}}
<script>
    (function () {
        var NAMES = {
            search: 'Pencarian', from: 'Dari tanggal', to: 'Sampai tanggal',
            type: 'Jenis transaksi', status: 'Status', canteen_id: 'Pilih kantin',
            santri_id: 'Pilih santri', kelas_id: 'Pilih kelas', branch_id: 'Pilih sekolah',
            due_date: 'Tanggal jatuh tempo', amount: 'Jumlah', description: 'Keterangan',
            admin_name: 'Nama lengkap admin', admin_email: 'Email admin',
            admin_password: 'Kata sandi admin', password: 'Kata sandi',
            password_confirmation: 'Ulangi kata sandi', year: 'Tahun ajaran'
        };

        function humanize(name) {
            return name
                .replace(/\[(\d+)\]/g, ' $1 ')
                .replace(/[\[\]]/g, ' ')
                .replace(/_/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/^./, function (c) { return c.toUpperCase(); });
        }

        function labelize(root) {
            var fields = root.querySelectorAll(
                'input:not([type=hidden]), select, textarea'
            );

            Array.prototype.forEach.call(fields, function (el) {
                if (el.hasAttribute('aria-label') || el.hasAttribute('aria-labelledby')) return;
                if (el.labels && el.labels.length) return;
                if (el.closest('label')) return;

                var placeholder = (el.getAttribute('placeholder') || '').trim();
                var name = el.getAttribute('name') || '';
                el.setAttribute('aria-label', placeholder || NAMES[name] || humanize(name) || 'Kolom input');
            });
        }

        labelize(document);

        if (window.MutationObserver) {
            new MutationObserver(function () { labelize(document); })
                .observe(document.body, { childList: true, subtree: true });
        }
    })();
</script>

@stack('scripts')
</body>
</html>
