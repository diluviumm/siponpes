@props([
    'label' => '',
    'value' => '',
    'sub' => null,
    'tone' => 'emerald',
    'valueClass' => null,
    'href' => null,
])

@php
    $tones = [
        'pine' => ['text-pine-700', 'bg-pine-50', 'border-l-pine-600'],
        'emerald' => ['text-pine-700', 'bg-pine-50', 'border-l-pine-600'],
        'blue' => ['text-sky-700', 'bg-sky-50', 'border-l-sky-600'],
        'amber' => ['text-gold-600', 'bg-gold-50', 'border-l-gold-500'],
        'purple' => ['text-purple-700', 'bg-purple-50', 'border-l-purple-600'],
        'indigo' => ['text-indigo-700', 'bg-indigo-50', 'border-l-indigo-600'],
        'rose' => ['text-rose-700', 'bg-rose-50', 'border-l-rose-600'],
        'orange' => ['text-orange-700', 'bg-orange-50', 'border-l-orange-600'],
        'teal' => ['text-teal-700', 'bg-teal-50', 'border-l-teal-600'],
        'red' => ['text-red-700', 'bg-red-50', 'border-l-red-600'],
    ];
    $c = $tones[$tone] ?? $tones['emerald'];
@endphp

@if($href)
<a href="{{ $href }}" class="stat-card border-l-4 {{ $c[2] }} group">
@else
<div class="stat-card border-l-4 {{ $c[2] }}">
@endif
    <div class="flex items-start justify-between gap-3">
        <p class="stat-card-label flex items-center gap-1.5">
            {{ $label }}
            @if($href)
                <svg class="w-3.5 h-3.5 text-pine-600 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            @endif
        </p>
        <div class="{{ $c[1] }} {{ $c[0] }} p-3 rounded-lg flex-shrink-0">
            @isset($icon)
                {{ $icon }}
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @endisset
        </div>
    </div>
    <div>
        <p class="{{ trim(($valueClass ?? '') . ' stat-card-value') }}">{{ $value }}</p>
        @if($sub)
            <p class="stat-card-sub flex items-center gap-1.5">
                <svg class="w-2 h-2 shrink-0 text-gold-500" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 10 5 5 10 0 5Z"/></svg>
                {{ $sub }}
            </p>
        @endif
    </div>
@if($href)
</a>
@else
</div>
@endif
