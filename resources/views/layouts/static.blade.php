<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIPONPES')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-ink antialiased min-h-screen flex flex-col">

    <header class="sticky top-0 z-40 bg-pine-950 text-white">
        <div class="max-w-4xl mx-auto px-4 lg:px-8 h-14 flex items-center gap-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-pine-950/40 ring-1 ring-gold-300/40 shrink-0">
                    <svg class="w-5 h-5 text-pine-950" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="font-display text-base font-semibold tracking-wide leading-tight truncate text-white">SIPONPES</h1>
                    <p class="text-[10px] text-pine-200 leading-tight">Sistem Informasi Ponpes</p>
                </div>
            </a>
            <a href="{{ route('landing') }}" class="ml-auto inline-flex items-center gap-1.5 text-sm text-pine-100/85 hover:text-white transition-colors whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Beranda
            </a>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-4 lg:px-8 py-10 w-full">
        @yield('content')
    </main>

    <footer class="px-4 lg:px-8 py-5 text-xs text-ink/40 text-center border-t border-ink/10">
        <div class="flex flex-wrap items-center justify-center gap-4">
            <span>SIPONPES — Sistem Informasi Pondok Pesantren</span>
            <a href="{{ route('privacy') }}" class="hover:underline">Kebijakan Privasi</a>
            <a href="{{ route('terms') }}" class="hover:underline">Syarat Layanan</a>
        </div>
    </footer>

</body>
</html>
