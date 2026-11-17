@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = 'inline-flex min-h-10 items-center justify-center rounded-lg border px-4 py-2 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf';
    $classes .= $variant === 'primary'
        ? ' border-forest bg-forest text-white hover:bg-ink'
        : ' border-border bg-white text-forest hover:bg-sage';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
