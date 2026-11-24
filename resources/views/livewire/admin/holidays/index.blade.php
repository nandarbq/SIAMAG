<div
    class="space-y-6"
    x-data
    x-effect="document.documentElement.classList.toggle('overflow-hidden', Boolean($wire.showForm))"
>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Kalender Kerja</p>
            <h1 class="page-title mt-1">Hari Libur</h1>
            <p class="page-subtitle">Atur hari libur nasional dan cuti bersama untuk perhitungan absensi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="sync"
                wire:loading.attr="disabled"
                wire:target="sync"
                class="btn-secondary"
            >
                <span wire:loading.class="animate-spin" wire:target="sync">
                    <x-icon name="refresh" class="h-4 w-4" />
                </span>
                <span wire:loading.remove wire:target="sync">Sinkronkan</span>
                <span wire:loading wire:target="sync">Menyinkronkan...</span>
            </button>
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Hari Libur
            </button>
        </div>
    </div>

    @error('year')
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror

    <div class="card card-body">
        <div class="grid gap-3 sm:grid-cols-[140px_1fr]">
            <input wire:model.live="year" type="number" min="2020" max="2100" class="input" placeholder="Tahun">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama hari libur..." class="input pl-10">
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($holidays as $holiday)
            <div class="card card-body animate-fade-up">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                            <span class="text-[10px] font-bold uppercase">{{ $holiday->date->translatedFormat('M') }}</span>
                            <span class="text-lg font-bold leading-none">{{ $holiday->date->format('d') }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900" title="{{ $holiday->name }}">{{ $holiday->name }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <p class="text-xs text-slate-500">{{ $holiday->date->translatedFormat('l, d F Y') }}</p>
                                @if ($holiday->source === \App\Models\Holiday::SOURCE_AUTO)
                                    <span class="badge-green badge-compact">Otomatis</span>
                                @else
                                    <span class="badge-slate badge-compact">Manual</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button type="button" wire:click="openEdit({{ $holiday->id }})" class="btn-ghost btn-sm" title="Edit">
                            <x-icon name="pencil" class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="$dispatch('app-confirm', {
                                title: 'Hapus hari libur?',
                                message: @js('Hari libur '.$holiday->name.' akan dihapus dari perhitungan absensi.'),
                                confirmLabel: 'Hapus hari libur',
                                workingLabel: 'Menghapus...',
                                onConfirm: () => $wire.delete({{ $holiday->id }}),
                            })"
                            class="btn-ghost btn-sm text-rose-500 hover:bg-rose-50 hover:text-rose-600"
                            title="Hapus"
                        >
                            <x-icon name="trash" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card card-body empty-state sm:col-span-2 xl:col-span-3">
                <x-icon name="calendar" class="h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-600">Belum ada hari libur</p>
                <p class="text-xs">Tambahkan hari libur untuk tahun {{ $year }}.</p>
            </div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="max-h-[calc(100dvh-2rem)] w-full max-w-md animate-fade-up overflow-y-auto overscroll-contain rounded-3xl bg-white p-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Edit Hari Libur' : 'Tambah Hari Libur' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Hari libur otomatis dikecualikan dari perhitungan alpha.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label class="label">Tanggal</label>
                        <input wire:model="date" type="date" class="input">
                        @error('date') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Nama Hari Libur</label>
                        <input wire:model="name" type="text" class="input" placeholder="Contoh: Hari Kemerdekaan RI">
                        @error('name') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
