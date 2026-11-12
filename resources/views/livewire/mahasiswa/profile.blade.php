<div class="space-y-6">
    <section>
        <p class="page-eyebrow">Akun</p>
        <h1 class="page-title">Profil Saya</h1>
        <p class="page-subtitle">Kelola informasi akun dan lihat data magang Anda.</p>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Informasi Akun</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Perbarui nama, email, dan nomor telepon Anda.</p>
                    </div>
                    <span class="stat-icon bg-brand-50 text-brand-600"><x-icon name="user" class="h-5 w-5" /></span>
                </div>

                <form wire:submit="updateProfile" class="card-body space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" wire:model="name" class="input" autocomplete="name">
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" wire:model="email" class="input" autocomplete="email">
                            @error('email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" wire:model="phone" class="input" placeholder="08xxxxxxxxxx" autocomplete="tel">
                            @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-4">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile" class="btn-primary">
                            <span wire:loading.remove wire:target="updateProfile">Simpan Perubahan</span>
                            <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Keamanan Akun</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Gunakan password yang kuat dan unik.</p>
                    </div>
                    <span class="stat-icon bg-violet-50 text-violet-600"><x-icon name="lock" class="h-5 w-5" /></span>
                </div>

                <form wire:submit="updatePassword" class="card-body space-y-4">
                    <div>
                        <label class="form-label">Password Saat Ini</label>
                        <input type="password" wire:model="currentPassword" class="input" autocomplete="current-password">
                        @error('currentPassword') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Password Baru</label>
                            <input type="password" wire:model="password" class="input" autocomplete="new-password">
                            @error('password') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" wire:model="passwordConfirmation" class="input" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-4">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="btn-primary">
                            <x-icon name="shield" class="h-4 w-4" />
                            <span wire:loading.remove wire:target="updatePassword">Perbarui Password</span>
                            <span wire:loading wire:target="updatePassword">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="relative h-24 bg-gradient-to-br from-brand-600 via-brand-700 to-slate-900">
                    <div class="absolute -right-6 -top-8 h-32 w-32 rounded-full bg-accent-400/30 blur-2xl"></div>
                </div>
                <div class="card-body -mt-12">
                    <div class="flex items-end justify-between gap-3">
                        <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-2xl font-bold text-brand-700 shadow-lg ring-4 ring-white">
                            {{ $user->initials() }}
                        </span>
                        @if ($user->is_active)
                            <span class="badge-green"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif</span>
                        @else
                            <span class="badge-red">Nonaktif</span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="badge-blue">Mahasiswa Magang</span>
                        @if ($user->nim)
                            <span class="badge-slate">NIM {{ $user->nim }}</span>
                        @endif
                        @if ($user->phone)
                            <span class="badge-slate">{{ $user->phone }}</span>
                        @endif
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-lg font-bold text-slate-900">{{ $attendanceCount }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Absensi</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-lg font-bold text-slate-900">{{ $logbookProgress['total'] }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Logbook</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-lg font-bold text-emerald-600">{{ $logbookProgress['percentage'] === null ? '—' : $logbookProgress['percentage'].'%' }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Progres</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Data Magang</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Dikelola oleh admin magang.</p>
                    </div>
                    <span class="stat-icon bg-accent-50 text-accent-600"><x-icon name="academic-cap" class="h-5 w-5" /></span>
                </div>

                @if ($profile)
                    <div class="card-body space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Progres magang</span>
                                <span class="font-bold text-slate-900">{{ $profile->progressPercent() }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" style="width: {{ $profile->progressPercent() }}%"></div>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">
                                {{ $profile->start_date?->translatedFormat('d M Y') }} — {{ $profile->end_date?->translatedFormat('d M Y') }}
                                · {{ $profile->remainingDays() }} hari tersisa
                            </p>
                        </div>

                        <dl class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50/60">
                            <div class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
                                <dt class="text-slate-500">Institusi</dt>
                                <dd class="max-w-[60%] text-right font-semibold text-slate-800">{{ $profile->institution }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
                                <dt class="text-slate-500">Jurusan</dt>
                                <dd class="max-w-[60%] text-right font-semibold text-slate-800">{{ $profile->major }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
                                <dt class="text-slate-500">Semester</dt>
                                <dd class="font-semibold text-slate-800">{{ $profile->semester ?: '—' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
                                <dt class="text-slate-500">Pembimbing Lapangan</dt>
                                <dd class="max-w-[60%] text-right font-semibold text-slate-800">{{ $profile->field_supervisor ?: '—' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
                                <dt class="text-slate-500">Kontak Pembimbing</dt>
                                <dd class="font-semibold text-slate-800">{{ $profile->supervisor_phone ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="card-body">
                        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
                            <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Data magang belum dilengkapi. Hubungi admin untuk mengisi institusi, jurusan, dan periode magang.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
