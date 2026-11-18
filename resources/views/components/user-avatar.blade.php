@props(['user', 'size' => 'md'])

@php
    $sizes = [
        'xs' => 'h-7 w-7 text-[10px]',
        'sm' => 'h-9 w-9 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-14 w-14 text-lg',
        'xl' => 'h-20 w-20 text-2xl',
        '2xl' => 'h-24 w-24 text-3xl',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $url = $user->avatarUrl();
@endphp

<span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-sm ring-1 ring-slate-900/5 {$sizeClass}"]) }}>
    @if ($url)
        <img src="{{ $url }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover" decoding="async">
    @else
        {{ $user->initials() }}
    @endif
</span>
