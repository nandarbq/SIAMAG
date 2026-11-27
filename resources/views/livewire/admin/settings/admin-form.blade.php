<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.settings') }}" class="btn-secondary px-2.5 py-2">
            <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
        </a>
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Pengaturan Sistem</p>
            <h1 class="page-title mt-1">{{ $admin ? 'Ubah Administrator' : 'Tambah Administrator' }}</h1>
            <p class="page-subtitle">
                {{ $admin ? 'Perbarui data akun administrator yang dipilih.' : 'Buat akun administrator baru untuk mengelola sistem.' }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <form wire:submit="save" class="card h-fit lg:col-span-2">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="stat-icon bg-sky-50 text-sky-600">
                        <x-icon name="shield" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Akun Administrator</h2>
                        <p class="text-xs text-slate-500">Informasi login dan status akses.</p>
                    </div>
                </div>
            </div>

            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label">Nama Lengkap</label>
                    <input wire:model.live.debounce.300ms="name" type="text" class="input" autocomplete="name" placeholder="Nama administrator">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email</label>
                    <input wire:model.live.debounce.300ms="email" type="email" class="input" autocomplete="email" placeholder="admin@bmkg.go.id">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <x-password-input
                    label="Password"
                    name="password"
                    wire:model="password"
                    :hint="$admin ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.'"
                />

                <div class="flex items-end">
                    <label class="flex w-full items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input wire:model.live="is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-700">Akun aktif</span>
                            <span class="block text-xs text-slate-500">Nonaktifkan jika belum digunakan.</span>
                        </span>
                    </label>
                    @error('is_active') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.settings') }}" class="btn-secondary w-full sm:w-auto">Batal</a>
                <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="save">
                    <x-icon name="check-circle" class="h-4 w-4" />
                    <span wire:loading.remove wire:target="save">{{ $admin ? 'Simpan Perubahan' : 'Simpan Admin' }}</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </form>

        <aside class="card h-fit lg:col-span-1">
            <div class="card-header">
                <h2 class="text-sm font-bold text-slate-900">Pratinjau Akun</h2>
                <p class="text-xs text-slate-500">Berubah saat Anda mengisi form.</p>
            </div>
            <div class="card-body space-y-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-brand-700 text-sm font-bold text-white">
                        {{ $name ? strtoupper(substr($name, 0, 1)) : 'A' }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $name ?: 'Nama administrator' }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $email ?: 'email@example.com' }}</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    @if ($is_active)
                        <span class="badge-green">Akun aktif</span>
                    @else
                        <span class="badge-slate">Akun nonaktif</span>
                    @endif
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                    <p class="font-semibold text-slate-700">Hak akses</p>
                    <p class="mt-1">Role akan diset sebagai <span class="font-semibold text-brand-600">Administrator</span>. Akun ini tidak terhubung ke data mahasiswa.</p>
                </div>
            </div>
        </aside>
    </div>
</div>
