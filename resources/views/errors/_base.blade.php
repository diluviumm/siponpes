<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $status ?? 'Error' }} — SIPONPES</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-ink antialiased min-h-screen flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-md text-center">

        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-pine-700 flex items-center justify-center shadow-lg mb-6">
                <svg class="w-9 h-9 text-gold-300" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a5.6 5.6 0 0 0 4.9 8.4c.2 0 .4 0 .6-.05.3 1 .4 2 .4 3a10 10 0 1 1-11.8-9.8A5.6 5.6 0 0 0 12 2Zm1.7 5.2a3.4 3.4 0 1 0 3.4 3.4 3.4 3.4 0 0 0-3.4-3.4Z"/>
                </svg>
            </div>
            <h1 class="font-display text-6xl font-semibold text-pine-900">{{ $code }}</h1>
            <x-page-title-rule width="w-full mt-4" />
            <p class="text-ink/60 mt-4">{{ $message }}</p>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('landing') }}" class="btn btn-primary btn-md">Kembali ke Beranda</a>
            @if(! auth()->check())
                <a href="{{ route('login') }}" class="btn btn-ghost btn-md">Masuk</a>
            @endif
        </div>
    </div>
</body>
</html>