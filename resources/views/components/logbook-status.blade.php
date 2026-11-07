@props(['status'])

@php
    $class = match ($status) {
        \App\Enums\LogbookStatus::Approved => 'badge-green',
        \App\Enums\LogbookStatus::Submitted => 'badge-amber',
        \App\Enums\LogbookStatus::Rejected => 'badge-red',
        default => 'badge-slate',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $status->label() }}</span>
