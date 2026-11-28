<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.students') }}" class="btn-secondary px-2.5 py-2">
            <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
        </a>
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Manajemen Magang</p>
            <h1 class="page-title mt-1">{{ $user ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' }}</h1>
            <p class="page-subtitle">{{ $user ? 'Perbarui data akun dan profil magang mahasiswa.' : 'Buat akun dan lengkapi profil magang mahasiswa baru.' }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-2 rounded-xl border border-brand-200 bg-brand-50/70 px-4 py-3 sm:flex-row sm:items-center sm:gap-3">
        <span class="shrink-0 rounded-full bg-brand-600 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Alur</span>
        <p class="text-sm text-brand-900">
            <span class="font-semibold">Buat atau pilih kelompok magang lebih dulu</span>, lalu isi data mahasiswa.
            <span class="text-brand-700">Kampus, pembimbing, dan periode terisi otomatis dari kelompok, tetapi dapat disesuaikan per mahasiswa.</span>
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="stat-icon bg-brand-50 text-brand-600">
                        <x-icon name="user" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Akun Mahasiswa</h2>
                        <p class="text-xs text-slate-500">Informasi login dan identitas utama.</p>
                    </div>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label">Nama lengkap <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="name" type="text" class="input" placeholder="Nama lengkap mahasiswa">
                    @error('name') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">NIM <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="nim" type="text" class="input" placeholder="Nomor induk mahasiswa">
                    @error('nim') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="email" type="email" class="input" placeholder="nama@kampus.ac.id">
                    @error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Nomor HP</label>
                    <input wire:model="phone" type="text" class="input" placeholder="08xxxxxxxxxx">
                    @error('phone') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <x-password-input
                    :label="$user ? 'Password (opsional)' : 'Password'"
                    name="password"
                    wire:model="password"
                    :required="! $user"
                    :placeholder="$user ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'"
                />
                <div class="flex items-end">
                    <label class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-700">Akun aktif</span>
                            <span class="block text-xs text-slate-500">Mahasiswa dapat login dan melakukan absensi.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="stat-icon bg-accent-50 text-accent-600">
                        <x-icon name="academic-cap" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Profil Magang</h2>
                        <p class="text-xs text-slate-500">Data kampus, periode, dan pembimbing lapangan.</p>
                    </div>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="label">Kelompok Magang <span class="font-normal text-rose-500">*</span></label>
                        <button type="button" wire:click="openGroupModal" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                            + Buat kelompok baru
                        </button>
                    </div>
                    <select wire:model.live="intern_group_id" class="input">
                        <option value="">Pilih kelompok magang</option>
                        @foreach ($groups as $groupOption)
                            <option value="{{ $groupOption->id }}">{{ $groupOption->code }} — {{ $groupOption->name }}{{ $groupOption->is_individual ? ' · Mandiri' : '' }}</option>
                        @endforeach
                    </select>
                    @error('intern_group_id') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    @if ($groupNotice)
                        <p class="mt-1.5 text-xs font-medium text-emerald-600">{{ $groupNotice }}</p>
                    @endif
                    @if ($groups->isEmpty())
                        <p class="mt-1.5 text-xs text-amber-700">
                            Belum ada kelompok aktif.
                            <button type="button" wire:click="openGroupModal" class="font-bold underline">Buat kelompok sekarang</button>
                        </p>
                    @endif
                    @if ($intern_group_id)
                        <p class="mt-1.5 text-xs text-slate-500">Nilai awal kampus, pembimbing, dan periode diambil dari kelompok. Anda dapat menyesuaikannya untuk mahasiswa ini.</p>
                    @endif
                </div>
                <div>
                    <label class="label">Institusi / Kampus <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="institution" type="text" class="input" placeholder="Nama universitas atau politeknik">
                    @error('institution') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Jurusan / Program Studi <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="major" type="text" class="input" placeholder="Contoh: Teknologi Informasi">
                    @error('major') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Semester <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="semester" type="number" min="1" max="14" class="input" placeholder="6">
                    @error('semester') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Pembimbing Lapangan</label>
                    <input wire:model="field_supervisor" type="text" class="input" placeholder="Nama pembimbing di BMKG">
                    @error('field_supervisor') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Tanggal Mulai <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="start_date" type="date" class="input">
                    @error('start_date') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Tanggal Selesai <span class="font-normal text-rose-500">*</span></label>
                    <input wire:model="end_date" type="date" class="input">
                    @error('end_date') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">No. HP Pembimbing</label>
                    <input wire:model="supervisor_phone" type="text" class="input" placeholder="08xxxxxxxxxx">
                    @error('supervisor_phone') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.students') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <x-icon name="check-circle" class="h-4 w-4" />
                <span wire:loading.remove>{{ $user ? 'Simpan Perubahan' : 'Simpan Mahasiswa' }}</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>

    @if ($groupModalOpen)
        <div
            class="fixed inset-0 z-[60] overflow-y-auto overscroll-contain bg-slate-950/70 p-0 backdrop-blur-sm sm:p-4"
            wire:click.self="closeGroupModal"
            role="dialog"
            aria-modal="true"
            aria-label="Tambah kelompok magang"
        >
            <div class="mx-auto flex min-h-full w-full max-w-3xl items-end sm:items-center">
                <div class="max-h-[calc(100dvh-1rem)] w-full overflow-y-auto overscroll-contain rounded-t-3xl bg-white shadow-2xl shadow-slate-950/30 sm:max-h-[calc(100dvh-2rem)] sm:rounded-3xl">
                    <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-5 py-3 sm:px-6">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-brand-600">Langkah 1</p>
                            <h2 class="text-base font-bold text-slate-900">Buat kelompok magang</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Setelah disimpan, kelompok ini otomatis dipilih untuk mahasiswa.</p>
                        </div>
                        <button type="button" wire:click="closeGroupModal" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="p-5 sm:p-6">
                        <livewire:admin.groups.form :embedded="true" :in-modal="true" wire:key="student-group-form" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
