@props([
    'label' => 'Password',
    'name' => null,
    'hint' => null,
    'placeholder' => null,
    'autocomplete' => 'new-password',
    'required' => false,
    'labelClass' => 'label',
    'inputClass' => '',
])

@php
    $inputAttributes = $attributes->except(['class'])->merge([
        'class' => trim('input pr-11 '.$inputClass),
        'autocomplete' => $autocomplete,
        'placeholder' => $placeholder,
        ':type' => 'show ? \'text\' : \'password\'',
    ]);
@endphp

<div class="w-full" x-data="{ show: false }">
    <label class="{{ $labelClass }}">{{ $label }}@if ($required) <span class="font-normal text-rose-500">*</span>@endif</label>
    <div class="relative">
        <input {{ $inputAttributes }}>
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40"
            @click="show = ! show"
            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
            :aria-pressed="show ? 'true' : 'false'"
            :title="show ? 'Sembunyikan password' : 'Tampilkan password'"
        >
            <x-icon name="eye" x-show="! show" class="h-4 w-4" />
            <x-icon name="eye-off" x-show="show" x-cloak class="h-4 w-4" />
        </button>
    </div>
    @if ($hint)
        <p class="mt-1 text-[11px] text-slate-500">{{ $hint }}</p>
    @endif
    @if ($name !== null)
        @error($name) <p class="form-error">{{ $message }}</p> @enderror
    @endif
</div>
