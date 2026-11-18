@props([
    'variant' => 'gajah-oling',
    'class' => '',
])

@if ($variant === 'gandrung')
    <svg
        {{ $attributes->merge(['class' => $class, 'aria-hidden' => 'true', 'focusable' => 'false']) }}
        viewBox="0 0 360 640"
        fill="none"
        preserveAspectRatio="xMidYMax meet"
        xmlns="http://www.w3.org/2000/svg"
    >
        <g fill="currentColor" transform="rotate(-3 180 320)">
            <path d="M158 96c-12-10-22-24-27-40 13 5 24 12 32 21 2-15 7-29 16-42 9 13 14 27 16 42 8-9 19-16 32-21-5 16-15 30-27 40h-42Z" />
            <path d="M160 94h40v12h-40z" />
            <circle cx="180" cy="122" r="21" />
            <path d="M170 140h20v18h-20z" />
            <path d="M154 156c8-6 18-9 26-9s18 3 26 9c8 6 12 16 12 28v74c0 8-6 14-14 14h-48c-8 0-14-6-14-14v-74c0-12 4-22 12-28Z" />
            <path d="M160 174c-18 12-32 30-42 52-5 11-14 16-24 16" stroke="currentColor" stroke-width="14" stroke-linecap="round" />
            <path d="M200 174c18 12 32 30 42 52 5 11 14 16 24 16" stroke="currentColor" stroke-width="14" stroke-linecap="round" />
            <circle cx="94" cy="242" r="9" />
            <circle cx="266" cy="242" r="9" />
            <path d="M156 258h48l42 330c1 8-5 14-13 14H127c-8 0-14-6-13-14l42-330Z" />
            <path d="M98 246c-28 8-46 24-58 48 18 2 34 8 48 18-18 22-28 46-30 72 22-18 46-28 72-30" stroke="currentColor" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M266 242l34-46" stroke="currentColor" stroke-width="8" stroke-linecap="round" />
            <path d="M300 196c16-16 36-24 56-22 0 22-9 42-25 56l-31-34Z" />
        </g>
    </svg>
@elseif ($variant === 'gajah-oling')
    @php($patternId = 'gajah-oling-'.\Illuminate\Support\Str::random(8))
    <svg
        {{ $attributes->merge(['class' => $class, 'aria-hidden' => 'true', 'focusable' => 'false']) }}
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <pattern id="{{ $patternId }}" width="88" height="88" patternUnits="userSpaceOnUse" patternTransform="rotate(-8)">
                <g fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M24 18c12-8 26-4 30 8 4 12-6 20-18 22-12 2-20 10-18 22 2 12 14 18 26 14" />
                    <path d="M20 18c-4-6-12-8-18-4" />
                    <path d="M54 26c10-2 18 2 22 10" />
                    <path d="M62 54c-2 10-8 18-18 22" />
                    <path d="M18 56c-6 8-8 18-4 28" />
                    <path d="M50 78c8 4 18 2 24-4" />
                    <path d="M6 34c4-4 10-4 14 0-4 4-10 4-14 0Z" />
                    <path d="M74 46c4-4 10-4 14 0-4 4-10 4-14 0Z" />
                    <circle cx="70" cy="16" r="2.5" fill="currentColor" stroke="none" />
                    <circle cx="14" cy="78" r="2.5" fill="currentColor" stroke="none" />
                    <circle cx="74" cy="70" r="1.8" fill="currentColor" stroke="none" />
                </g>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#{{ $patternId }})" />
    </svg>
@endif
