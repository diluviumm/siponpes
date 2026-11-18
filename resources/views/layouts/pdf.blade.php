<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - SIPONPES</title>
    <style>
        @page { margin-bottom: 32mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.4; }
        .header { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 14px; }
        .header h1 { font-size: 17px; margin: 0 0 2px; }
        .muted { color: #555; font-size: 10px; margin: 0; }
        h2 { font-size: 13px; margin: 16px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #efefef; font-size: 10px; text-transform: uppercase; }
        .num { text-align: right; }
        .total td { font-weight: bold; background: #f5f5f5; }
        .strong { font-weight: bold; }
        .footer { position: fixed; bottom: 4mm; left: 0; right: 0; width: 100%; text-align: center; }
        .footer .muted { font-size: 9px; }
        .balance td { font-weight: bold; background: #f5f5f5; }
        .signature-block { position: fixed; bottom: 18mm; left: 0; right: 0; width: 100%; }
        .signature-block table { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIPONPES — {{ $title }}</h1>
        <p class="muted">{{ $subtitle }}</p>
    </div>

    @yield('content')

    <div class="footer">
        <p class="muted">Dicetak {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y H:i') }} melalui Sistem Informasi Pondok Pesantren (SIPONPES).</p>
    </div>
</body>
</html>
