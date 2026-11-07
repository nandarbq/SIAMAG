<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Informasi</p>
            <h1 class="page-title mt-1">Pengumuman</h1>
            <p class="page-subtitle">Bagikan informasi penting kepada seluruh mahasiswa magang.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Buat Pengumuman
        </button>
    </div>

    <div class="card card-body">
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari pengumuman..." class="input pl-10">
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($announcements as $announcement)
            <article class="card card-body animate-fade-up">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="stat-icon shrink-0 bg-brand-50 text-brand-600">
                            <x-icon name="megaphone" class="h-5 w-5" />
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">{{ $announcement->title }}</h2>
                                @if ($announcement->is_active)
                                    <span class="badge-green">Aktif</span>
                                @else
                                    <span class="badge-slate">Nonaktif</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-400">{{ $announcement->published_at?->translatedFormat('d F Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ Str::limit($announcement->body, 220) }}</p>

                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                    <button type="button" wire:click="toggleActive({{ $announcement->id }})" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                        {{ $announcement->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <div class="flex gap-1">
                        <button type="button" wire:click="openEdit({{ $announcement->id }})" class="btn-ghost btn-sm">
                            <x-icon name="pencil" class="h-4 w-4" />
                            Edit
                        </button>
                        <button
                            type="button"
                            wire:click="delete({{ $announcement->id }})"
                            wire:confirm="Hapus pengumuman ini?"
                            class="btn-ghost btn-sm text-rose-500 hover:bg-rose-50 hover:text-rose-600"
                        >
                            <x-icon name="trash" class="h-4 w-4" />
                            Hapus
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="card card-body empty-state lg:col-span-2">
                <x-icon name="megaphone" class="h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-600">Belum ada pengumuman</p>
                <p class="text-xs">Buat pengumuman pertama untuk mahasiswa magang.</p>
            </div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="w-full max-w-xl animate-fade-up rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Edit Pengumuman' : 'Buat Pengumuman' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Pengumuman aktif akan tampil di dashboard mahasiswa.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label class="label">Judul</label>
                        <input wire:model="title" type="text" class="input" placeholder="Judul pengumuman">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Isi Pengumuman</label>
                        <textarea wire:model="body" rows="6" class="input" placeholder="Tulis informasi yang ingin disampaikan..."></textarea>
                        @error('body') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-700">Aktifkan pengumuman</span>
                            <span class="block text-xs text-slate-500">Tampilkan di dashboard mahasiswa.</span>
                        </span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeForm" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
