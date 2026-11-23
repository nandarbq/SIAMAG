@props(['status', 'count' => null])

@php
    [$class, $icon] = match ($status) {
        \App\Enums\AttendanceStatus::Hadir => ['badge-green', 'check-circle'],
        \App\Enums\AttendanceStatus::Terlambat => ['badge-amber', 'clock'],
        \App\Enums\AttendanceStatus::Izin => ['badge-sky', 'calendar'],
        \App\Enums\AttendanceStatus::Sakit => ['badge-violet', 'heart'],
        \App\Enums\AttendanceStatus::Alpha => ['badge-red', 'x'],
        default => ['badge-slate', 'calendar'],
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    <x-icon :name="$icon" class="h-3.5 w-3.5" />
    {{ $status->label() }}
    @if ($count !== null)
        <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold">{{ (int) $count }}</span>
    @endif
</span>
