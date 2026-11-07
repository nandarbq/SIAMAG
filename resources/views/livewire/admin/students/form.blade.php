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
                    <label class="label">Nama lengkap</label>
                    <input wire:model="name" type="text" class="input" placeholder="Nama lengkap mahasiswa">
                    @error('name') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">NIM</label>
                    <input wire:model="nim" type="text" class="input" placeholder="Nomor induk mahasiswa">
                    @error('nim') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email</label>
                    <input wire:model="email" type="email" class="input" placeholder="nama@kampus.ac.id">
                    @error('email') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Nomor HP</label>
                    <input wire:model="phone" type="text" class="input" placeholder="08xxxxxxxxxx">
                    @error('phone') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Password {{ $user ? '(opsional)' : '' }}</label>
                    <input wire:model="password" type="password" class="input" placeholder="{{ $user ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}">
                    @error('password') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
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
                <div>
                    <label class="label">Institusi / Kampus</label>
                    <input wire:model="institution" type="text" class="input" placeholder="Nama universitas atau politeknik">
                    @error('institution') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Jurusan / Program Studi</label>
                    <input wire:model="major" type="text" class="input" placeholder="Contoh: Teknologi Informasi">
                    @error('major') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Semester</label>
                    <input wire:model="semester" type="number" min="1" max="14" class="input" placeholder="6">
                    @error('semester') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Pembimbing Lapangan</label>
                    <input wire:model="field_supervisor" type="text" class="input" placeholder="Nama pembimbing di BMKG">
                    @error('field_supervisor') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Tanggal Mulai</label>
                    <input wire:model="start_date" type="date" class="input">
                    @error('start_date') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Tanggal Selesai</label>
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
</div>
