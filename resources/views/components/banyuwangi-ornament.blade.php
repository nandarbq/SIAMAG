@props([
    'variant' => 'gajah-oling',
    'class' => '',
])

@if ($variant === 'gandrung')
    <img
        {{ $attributes->merge(['class' => $class, 'alt' => '', 'aria-hidden' => 'true', 'draggable' => 'false']) }}
        src="{{ asset('images/culture/gandrung-silhouette.png') }}"
        width="382"
        height="723"
        decoding="async"
    >
@elseif ($variant === 'gajah-oling')
    <div
        {{ $attributes->merge(['class' => $class, 'aria-hidden' => 'true']) }}
        style="background-image: url('{{ asset('images/culture/gajah-oling-halfdrop-tile.png') }}'); background-repeat: repeat; background-size: 192px 192px; background-position: center;"
    ></div>
@endif
