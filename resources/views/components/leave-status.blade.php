@props(['status'])

@php
    $class = match ($status) {
        \App\Enums\LeaveStatus::Approved => 'badge-green',
        \App\Enums\LeaveStatus::Pending => 'badge-amber',
        \App\Enums\LeaveStatus::Rejected => 'badge-red',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $status->label() }}</span>
