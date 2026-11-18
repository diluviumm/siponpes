{{-- Indikator arah sortir untuk header tabel. Memakai variabel $column --}}
@props(['column'])

@if(request('sort') === $column)
    <svg class="w-3 h-3 text-pine-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        @if(request('direction') === 'asc')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        @else
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        @endif
    </svg>
@endif