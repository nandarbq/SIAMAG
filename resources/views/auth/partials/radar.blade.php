@php
    $id ??= 'auth-radar';
@endphp

<div class="auth-radar-device relative aspect-[1335/1178] w-full translate-y-2.5">
    <div
        class="auth-radar-art absolute inset-0"
        style="--radar-art: url('{{ asset('images/auth/antena-radar-satelit-modern.png') }}')"
        aria-hidden="true"
    ></div>

    <svg
        class="auth-radar-waves absolute inset-0 h-full w-full"
        viewBox="0 0 1335 1178"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <defs>
            <linearGradient id="{{ $id }}-glow" x1="120" y1="330" x2="900" y2="900" gradientUnits="userSpaceOnUse">
                <stop stop-color="#3b82f6" stop-opacity="0.28" />
                <stop offset="1" stop-color="#3b82f6" stop-opacity="0" />
            </linearGradient>
            <linearGradient id="{{ $id }}-wave" x1="0" y1="335" x2="230" y2="335" gradientUnits="userSpaceOnUse">
                <stop stop-color="#60a5fa" stop-opacity="0.04" />
                <stop offset="1" stop-color="#bae6fd" stop-opacity="0.95" />
            </linearGradient>
        </defs>

        <ellipse cx="610" cy="610" rx="520" ry="560" fill="url(#{{ $id }}-glow)" />

        <g class="auth-radar-wave-field">
            <g stroke="url(#{{ $id }}-wave)" stroke-linecap="round" fill="none">
                <path class="auth-radar-wave-static" d="M188.3 304.4A40 40 0 0 0 188.3 365.6" stroke-width="1.8" />
                <path class="auth-radar-wave-static" d="M163.9 275.2A78 78 0 0 0 163.9 394.8" stroke-width="2.2" />
                <path class="auth-radar-wave-static" d="M139.4 246.1A116 116 0 0 0 139.4 423.9" stroke-width="2.6" />
                <path class="auth-radar-wave-static" d="M115 217A154 154 0 0 0 115 453" stroke-width="3" />
                <path class="auth-radar-wave-static" d="M90.6 187.9A192 192 0 0 0 90.6 482.1" stroke-width="3.4" />

                <path class="auth-radar-wave" style="--wave-delay: 0s" d="M188.3 304.4A40 40 0 0 0 188.3 365.6" stroke-width="1.8" />
                <path class="auth-radar-wave" style="--wave-delay: 0.45s" d="M163.9 275.2A78 78 0 0 0 163.9 394.8" stroke-width="2.2" />
                <path class="auth-radar-wave" style="--wave-delay: 0.9s" d="M139.4 246.1A116 116 0 0 0 139.4 423.9" stroke-width="2.6" />
                <path class="auth-radar-wave" style="--wave-delay: 1.35s" d="M115 217A154 154 0 0 0 115 453" stroke-width="3" />
                <path class="auth-radar-wave" style="--wave-delay: 1.8s" d="M90.6 187.9A192 192 0 0 0 90.6 482.1" stroke-width="3.4" />
            </g>

            <g>
                <circle class="auth-radar-blip-ring" cx="214" cy="335" r="10" stroke="#fbbf24" stroke-opacity="0.8" stroke-width="2.5" />
                <circle class="auth-radar-blip-core" cx="214" cy="335" r="4" fill="#fbbf24" />
            </g>
        </g>
    </svg>
</div>
