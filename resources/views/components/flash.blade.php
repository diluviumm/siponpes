@props(['type' => 'success'])

<div class="flash flash-{{ $type }}" role="alert">{{ $slot }}</div>
