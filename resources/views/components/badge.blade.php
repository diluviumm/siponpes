@props(['tone' => 'slate'])

<span {{ $attributes->merge(['class' => 'badge badge-' . $tone]) }}>{{ $slot }}</span>
