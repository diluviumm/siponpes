<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPONPES — Sistem Informasi Pondok Pesantren</title>
    <meta name="description" content="SIPONPES: sistem informasi manajemen pondok pesantren multi-sekolah — data santri, tabungan, tagihan SPP, kasir kantin, dan laporan keuangan.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-ink antialiased min-h-screen flex flex-col">

    {{-- ============================================================
         NAVBAR
    ============================================================ --}}
    <header id="top" class="sticky top-0 z-40 bg-pine-950/95 backdrop-blur-sm border-b border-white/10">
        <div class="max-w-6xl mx-auto px-4 lg:px-8 h-16 flex items-center gap-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-pine-950/40 ring-1 ring-gold-300/40 shrink-0">
                    <svg class="w-5 h-5 text-pine-950" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="font-display text-lg font-semibold tracking-wide leading-tight truncate text-white">SIPONPES</h1>
                    <p class="text-[11px] text-pine-200 leading-tight">Sistem Informasi Ponpes</p>
                </div>
            </a>

            {{-- Nav desktop --}}
            <nav class="hidden md:flex items-center gap-1 ml-8 text-sm" aria-label="Navigasi utama">
                <a href="#fitur" class="px-3 py-2 rounded-lg text-pine-100/85 hover:text-white hover:bg-white/10 transition-colors font-medium">Fitur</a>
                <a href="#cara-kerja" class="px-3 py-2 rounded-lg text-pine-100/85 hover:text-white hover:bg-white/10 transition-colors font-medium">Cara Kerja</a>
            </nav>

            <div class="ml-auto hidden md:flex items-center gap-3 whitespace-nowrap">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-pine-100 border border-white/20 hover:bg-white/10 hover:text-white hover:border-white/40 transition-colors">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gold-500 text-pine-950 text-sm font-semibold hover:bg-gold-400 transition-colors shadow-lg shadow-gold-600/20">
                    Daftar Sekolah
                </a>
            </div>

            {{-- Tombol menu mobile --}}
            <button id="menuToggle" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="mobileMenu"
                    class="ml-auto md:hidden p-2 rounded-lg text-pine-100 hover:bg-white/10 transition-colors cursor-pointer">
                <svg id="menuOpenIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg id="menuCloseIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Menu mobile --}}
        <nav id="mobileMenu" class="hidden md:hidden border-t border-white/10 bg-pine-950" aria-label="Navigasi mobile">
            <div class="px-4 py-3 space-y-1">
                <a href="#fitur" class="block px-3 py-2.5 rounded-lg text-pine-100/90 hover:text-white hover:bg-white/10 transition-colors font-medium">Fitur</a>
                <a href="#cara-kerja" class="block px-3 py-2.5 rounded-lg text-pine-100/90 hover:text-white hover:bg-white/10 transition-colors font-medium">Cara Kerja</a>
                <div class="pt-2 flex gap-2">
                    <a href="{{ route('login') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-pine-100 border border-white/20 hover:bg-white/10 transition-colors">Masuk</a>
                    <a href="{{ route('register') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gold-500 text-pine-950 text-sm font-semibold hover:bg-gold-400 transition-colors">Daftar Sekolah</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-1">

        {{-- ============================================================
             HERO
        ============================================================ --}}
        <section class="relative overflow-hidden bg-gradient-to-b from-pine-900 to-pine-950 text-white">
            {{-- Latar dekoratif: pola ◆ halus + glow --}}
            <div class="absolute inset-0 opacity-60" aria-hidden="true"
                 style="background-image:url('data:image/svg+xml,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2748%27%20height=%2748%27%20viewBox=%270%200%2048%2048%27%3E%3Cpath%20d=%27M24%204%2044%2024%2024%2044%204%2024Z%27%20fill=%27none%27%20stroke=%27%23ffffff%27%20stroke-opacity=%270.05%27/%3E%3C/svg%3E'); background-size:48px 48px;"></div>
            <div class="absolute -top-28 -right-28 w-96 h-96 rounded-full bg-gold-500/15 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-40 -left-28 w-[30rem] h-[30rem] rounded-full bg-pine-500/25 blur-3xl" aria-hidden="true"></div>

            <div class="relative max-w-6xl mx-auto px-4 lg:px-8 py-20 lg:py-28 text-center">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-pine-800 text-gold-300 ring-1 ring-gold-400/30 mb-8">
                    <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                    Satu platform, banyak sekolah
                </span>

                <h2 class="font-display text-4xl lg:text-6xl font-semibold leading-tight tracking-tight text-balance">
                    Manajemen Pondok Pesantren<br class="hidden sm:block"> dalam satu aplikasi
                </h2>

                <p class="mt-6 text-pine-100/85 text-lg max-w-2xl mx-auto">
                    Kelola santri, tabungan, tagihan SPP, kasir kantin, hingga laporan keuangan secara rapi dan aman — oleh satu superadmin untuk banyak sekolah.
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="btn btn-gold btn-lg !text-black shadow-xl shadow-gold-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Daftarkan Sekolah Anda
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-lg text-pine-100 border border-white/25 hover:bg-white/10 hover:text-white hover:border-white/40">
                        Masuk ke Dashboard
                    </a>
                </div>

                {{-- Baris kepercayaan --}}
                <div class="mt-12 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-pine-200/80">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-2 h-2 text-gold-400" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                        Multi-sekolah
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-2 h-2 text-gold-400" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                        Verifikasi email
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-2 h-2 text-gold-400" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                        Audit trail
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-2 h-2 text-gold-400" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                        Backup rutin
                    </span>
                </div>
            </div>
        </section>

        {{-- ============================================================
             FITUR
        ============================================================ --}}
        <section id="fitur" class="max-w-6xl mx-auto px-4 lg:px-8 py-16 lg:py-24 scroll-mt-20">
            <div class="text-center mb-12">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-gold-100 text-pine-900 mb-4">
                    <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                    Fitur Utama
                </span>
                <h3 class="font-display text-3xl lg:text-4xl font-semibold text-pine-900">Semua kebutuhan administrasi, satu tempat</h3>
                <x-page-title-rule width="max-w-xs mx-auto mt-5" />
                <p class="text-ink/60 mt-5 max-w-2xl mx-auto">Dari pendataan santri hingga laporan keuangan — dirancang khusus untuk kebutuhan pondok pesantren.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                <div class="card p-6 group hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-pine-100 text-pine-700 flex items-center justify-center mb-4 group-hover:bg-pine-700 group-hover:text-gold-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                    </div>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Data Santri &amp; Kelas</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Kelola data santri, pembagian kelas, dan import massal dari Excel.</p>
                </div>

                <div class="card p-6 group hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-gold-100 text-gold-600 flex items-center justify-center mb-4 group-hover:bg-gold-500 group-hover:text-pine-950 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Tabungan Santri</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Simpanan santri dengan saldo, batas belanja harian, dan riwayat transaksi jelas.</p>
                </div>

                <div class="card p-6 group hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-pine-100 text-pine-700 flex items-center justify-center mb-4 group-hover:bg-pine-700 group-hover:text-gold-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Tagihan SPP</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Buat invoice otomatis, kirim pengingat, dan pantau pembayaran via wallet.</p>
                </div>

                <div class="card p-6 group hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-gold-100 text-gold-600 flex items-center justify-center mb-4 group-hover:bg-gold-500 group-hover:text-pine-950 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Kasir Kantin</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Jualan kantin cepat dengan stok menu, dua metode bayar, dan rekap harian.</p>
                </div>

                <div class="card p-6 group hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-pine-100 text-pine-700 flex items-center justify-center mb-4 group-hover:bg-pine-700 group-hover:text-gold-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Laporan Keuangan</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Jurnal, buku besar, neraca, laba rugi, arus kas, dan export PDF/Excel.</p>
                </div>

                <div class="card p-6 group hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-11 h-11 rounded-xl bg-gold-100 text-gold-600 flex items-center justify-center mb-4 group-hover:bg-gold-500 group-hover:text-pine-950 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a2 2 0 10-2-2m0 0v6m0-6a2 2 0 112 2m-2 0h2m2 5h-8a2 2 0 01-2-2V8a2 2 0 012-2h1M15 5a2 2 0 012 2m-2-4a4 4 0 014 4v2a4 4 0 01-4 4m0-6h4"/></svg>
                    </div>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Aman &amp; Telusur</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Verifikasi email, audit trail, backup rutin, dan isolasi data antar sekolah.</p>
                </div>

            </div>
        </section>

        {{-- ============================================================
             BAND KEUNGGULAN
        ============================================================ --}}
        <section class="bg-pine-950 text-white relative overflow-hidden">
            <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative max-w-6xl mx-auto px-4 lg:px-8 py-14">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="w-12 h-12 mx-auto rounded-xl bg-pine-800 text-gold-300 flex items-center justify-center ring-1 ring-gold-400/20 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </div>
                        <p class="font-display text-lg font-semibold">6 Modul Inti</p>
                        <p class="text-sm text-pine-200/70 mt-1">Satu platform terpadu</p>
                    </div>
                    <div>
                        <div class="w-12 h-12 mx-auto rounded-xl bg-pine-800 text-gold-300 flex items-center justify-center ring-1 ring-gold-400/20 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <p class="font-display text-lg font-semibold">Multi-Sekolah</p>
                        <p class="text-sm text-pine-200/70 mt-1">Satu superadmin, banyak pesantren</p>
                    </div>
                    <div>
                        <div class="w-12 h-12 mx-auto rounded-xl bg-pine-800 text-gold-300 flex items-center justify-center ring-1 ring-gold-400/20 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a2 2 0 10-2-2m0 0v6m0-6a2 2 0 112 2m-2 0h2m2 5h-8a2 2 0 01-2-2V8a2 2 0 012-2h1M15 5a2 2 0 012 2m-2-4a4 4 0 014 4v2a4 4 0 01-4 4m0-6h4"/></svg>
                        </div>
                        <p class="font-display text-lg font-semibold">Data Terisolasi</p>
                        <p class="text-sm text-pine-200/70 mt-1">Keamanan data antar sekolah</p>
                    </div>
                    <div>
                        <div class="w-12 h-12 mx-auto rounded-xl bg-pine-800 text-gold-300 flex items-center justify-center ring-1 ring-gold-400/20 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        </div>
                        <p class="font-display text-lg font-semibold">Export PDF / Excel</p>
                        <p class="text-sm text-pine-200/70 mt-1">Laporan siap cetak &amp; unduh</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================
             CARA KERJA
        ============================================================ --}}
        <section id="cara-kerja" class="max-w-6xl mx-auto px-4 lg:px-8 py-16 lg:py-24 scroll-mt-20">
            <div class="text-center mb-12">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-gold-100 text-pine-900 mb-4">
                    <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                    Cara Kerja
                </span>
                <h3 class="font-display text-3xl lg:text-4xl font-semibold text-pine-900">Mulai dalam 3 langkah mudah</h3>
                <x-page-title-rule width="max-w-xs mx-auto mt-5" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                <div class="card p-6 text-center relative hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-pine-700 text-gold-300 flex items-center justify-center shadow-lg shadow-pine-700/20 mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gold-500 text-pine-950 text-sm font-bold absolute top-4 right-4">1</span>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Daftarkan Sekolah</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Isi formulir pendaftaran dengan data sekolah dan akun admin.</p>
                </div>

                <div class="card p-6 text-center relative hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-pine-700 text-gold-300 flex items-center justify-center shadow-lg shadow-pine-700/20 mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gold-500 text-pine-950 text-sm font-bold absolute top-4 right-4">2</span>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Disetujui Admin</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Tunggu persetujuan superadmin lalu verifikasi email Anda.</p>
                </div>

                <div class="card p-6 text-center relative hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-pine-700 text-gold-300 flex items-center justify-center shadow-lg shadow-pine-700/20 mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gold-500 text-pine-950 text-sm font-bold absolute top-4 right-4">3</span>
                    <h4 class="font-display text-lg font-semibold text-pine-900">Kelola &amp; Pantau</h4>
                    <p class="text-sm text-ink/60 mt-1.5 leading-relaxed">Input santri, tagihan, kasir kantin, dan pantau laporan keuangan.</p>
                </div>

            </div>
        </section>

        {{-- ============================================================
             CTA
        ============================================================ --}}
        <section class="relative overflow-hidden bg-pine-900 text-white">
            <div class="absolute inset-0 opacity-40" aria-hidden="true"
                 style="background-image:url('data:image/svg+xml,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2748%27%20height=%2748%27%20viewBox=%270%200%2048%2048%27%3E%3Cpath%20d=%27M24%204%2044%2024%2024%2044%204%2024Z%27%20fill=%27none%27%20stroke=%27%23ffffff%27%20stroke-opacity=%270.05%27/%3E%3C/svg%3E'); background-size:48px 48px;"></div>
            <div class="relative max-w-6xl mx-auto px-4 lg:px-8 py-16 lg:py-20 text-center">
                <h3 class="font-display text-3xl lg:text-4xl font-semibold">Siap dikelola pesantren Anda?</h3>
                <p class="text-pine-100/85 mt-4 max-w-xl mx-auto text-lg">
                    Daftarkan sekolah Anda sekarang. Setelah disetujui admin, Anda langsung bisa mengelola data dan keuangan pesantren.
                </p>
                <a href="{{ route('register') }}" class="btn btn-gold btn-lg mt-8 !text-black shadow-xl shadow-gold-600/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Daftar Sekolah
                </a>
            </div>
        </section>

    </main>

    {{-- ============================================================
         FOOTER
    ============================================================ --}}
    <footer class="bg-pine-950 text-pine-200">
        <div class="max-w-6xl mx-auto px-4 lg:px-8 py-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="lg:col-span-2 max-w-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center ring-1 ring-gold-300/40">
                        <svg class="w-5 h-5 text-pine-950" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-display text-lg font-semibold text-white">SIPONPES</p>
                        <p class="text-[11px] text-pine-300/70">Sistem Informasi Pondok Pesantren</p>
                    </div>
                </div>
                <p class="text-sm text-pine-200/70 leading-relaxed">
                    Platform manajemen pesantren multi-sekolah: data santri, tabungan, tagihan SPP, kasir kantin, dan laporan keuangan dalam satu aplikasi yang rapi dan aman.
                </p>
                <div class="mt-4 flex items-center gap-2 text-gold-500/60" aria-hidden="true">
                    <span class="h-px flex-1 bg-gradient-to-r from-transparent to-gold-500/50"></span>
                    <svg class="w-2 h-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                    <span class="h-px flex-1 bg-gradient-to-l from-transparent to-gold-500/50"></span>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gold-300/80 mb-4">Fitur</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="#fitur" class="text-pine-200/70 hover:text-white transition-colors">Data Santri &amp; Kelas</a></li>
                    <li><a href="#fitur" class="text-pine-200/70 hover:text-white transition-colors">Tabungan Santri</a></li>
                    <li><a href="#fitur" class="text-pine-200/70 hover:text-white transition-colors">Tagihan SPP</a></li>
                    <li><a href="#fitur" class="text-pine-200/70 hover:text-white transition-colors">Kasir Kantin</a></li>
                    <li><a href="#fitur" class="text-pine-200/70 hover:text-white transition-colors">Laporan Keuangan</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gold-300/80 mb-4">Mulai</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('register') }}" class="text-pine-200/70 hover:text-white transition-colors">Daftar Sekolah</a></li>
                    <li><a href="{{ route('login') }}" class="text-pine-200/70 hover:text-white transition-colors">Masuk</a></li>
                    <li><a href="{{ route('password.request') }}" class="text-pine-200/70 hover:text-white transition-colors">Lupa Password</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="max-w-6xl mx-auto px-4 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-pine-300/70">
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <p>&copy; {{ date('Y') }} SIPONPES — Sistem Informasi Pondok Pesantren.</p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Kebijakan Privasi</a>
                        <a href="{{ route('terms') }}" class="hover:text-white transition-colors">Syarat Layanan</a>
                    </div>
                </div>
                <a href="#top" class="inline-flex items-center gap-1.5 hover:text-white transition-colors">
                    Kembali ke atas
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </a>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('menuToggle').addEventListener('click', function () {
            var menu = document.getElementById('mobileMenu');
            var isHidden = menu.classList.toggle('hidden');
            document.getElementById('menuOpenIcon').classList.toggle('hidden', !isHidden);
            document.getElementById('menuCloseIcon').classList.toggle('hidden', isHidden);
            this.setAttribute('aria-expanded', String(!isHidden));
        });
    </script>

</body>
</html>
