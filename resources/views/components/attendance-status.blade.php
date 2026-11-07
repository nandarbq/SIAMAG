@props(['status'])

@php
    $class = match ($status) {
        \App\Enums\AttendanceStatus::Hadir => 'badge-green',
        \App\Enums\AttendanceStatus::Terlambat => 'badge-amber',
        \App\Enums\AttendanceStatus::Izin, \App\Enums\AttendanceStatus::Sakit => 'badge-blue',
        \App\Enums\AttendanceStatus::Alpha => 'badge-red',
        default => 'badge-slate',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $status->label() }}</span>
