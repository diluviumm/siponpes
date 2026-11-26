@props(['items' => []])

@php
    $items = array_values(array_filter($items));
@endphp

@if(count($items))
    <nav class="breadcrumb" aria-label="Breadcrumb">
        @foreach($items as $item)
            @if(! $loop->first)
                <span>/</span>
            @endif
            @if(is_array($item))
                <a href="{{ $item['url'] }}" class="breadcrumb-link">{{ $item['label'] }}</a>
            @else
                <span class="font-medium text-ink">{{ $item }}</span>
            @endif
        @endforeach
    </nav>
@endif
