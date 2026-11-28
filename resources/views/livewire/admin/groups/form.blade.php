<div class="mx-auto max-w-4xl space-y-4">
    @unless ($inModal)
        <div class="flex items-center gap-3">
            @if ($embedded)
                <button type="button" wire:click="$dispatch('back-to-groups')" class="btn-secondary px-2.5 py-2">
                    <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
                </button>
            @else
                <a href="{{ route('admin.groups') }}" class="btn-secondary px-2.5 py-2">
                    <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
                </a>
            @endif
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-brand-600">Kelompok</p>
                <h2 class="text-lg font-bold text-slate-900">{{ $group ? 'Edit kelompok' : 'Tambah kelompok' }}</h2>
            </div>
        </div>
    @endunless

    <form wire:submit="save" class="card card-body space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Kode <span class="font-normal text-rose-500">*</span></label>
                <input wire:model="code" type="text" class="input" placeholder="MAG-2026-001">
                @error('code') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Nama kelompok <span class="font-normal text-rose-500">*</span></label>
                <input wire:model="name" type="text" class="input" placeholder="Contoh: Kelompok 1 - Poliwangi">
                @error('name') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Kampus <span class="font-normal text-rose-500">*</span></label>
                <input wire:model="institution" type="text" class="input" placeholder="Nama universitas atau politeknik">
                @error('institution') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Divisi <span class="font-normal text-slate-400">(opsional)</span></label>
                <input wire:model="division" type="text" class="input" placeholder="Contoh: Divisi IT">
                @error('division') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Pembimbing lapangan</label>
                <input wire:model="field_supervisor" type="text" class="input" placeholder="Nama pembimbing di BMKG">
                @error('field_supervisor') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">No. HP pembimbing</label>
                <input wire:model="supervisor_phone" type="text" class="input" placeholder="08xxxxxxxxxx">
                @error('supervisor_phone') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Mulai <span class="font-normal text-rose-500">*</span></label>
                <input wire:model="start_date" type="date" class="input">
                @error('start_date') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Selesai <span class="font-normal text-rose-500">*</span></label>
                <input wire:model="end_date" type="date" class="input">
                @error('end_date') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700">
                <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                Kelompok aktif
            </label>
            <div>
                <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700">
                    <input wire:model.live="is_individual" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                    Magang mandiri (1 mahasiswa)
                </label>
                <p class="mt-1.5 text-xs text-slate-500">Centang jika kelompok ini hanya diisi satu mahasiswa. Kode otomatis akan memakai penanda IND.</p>
                @error('is_individual') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
            @if ($embedded)
                <button type="button" wire:click="$dispatch('back-to-groups')" class="btn-secondary">Batal</button>
            @else
                <a href="{{ route('admin.groups') }}" class="btn-secondary">Batal</a>
            @endif
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <x-icon name="check-circle" class="h-4 w-4" />
                <span wire:loading.remove>{{ $group ? 'Simpan Perubahan' : 'Simpan Kelompok' }}</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
