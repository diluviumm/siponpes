@props(['buttons' => []])

<div class="btn-group" role="group" aria-label="Ekspor laporan">
    @foreach($buttons as $i => $btn)
        @if($i > 0)
            <span class="w-px h-6 bg-ink/15 shrink-0"></span>
        @endif
        <a href="{{ $btn['url'] }}" class="btn btn-secondary btn-md">{{ $btn['label'] }}</a>
    @endforeach
</div>
