<div class="space-y-6">
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="page-eyebrow">Akun</p>
            <h1 class="page-title">Profil Saya</h1>
            <p class="page-subtitle">Kelola informasi akun dan pantau progres magang Anda.</p>
        </div>
        <span class="badge-blue self-start sm:self-auto">
            <x-icon name="academic-cap" class="h-3.5 w-3.5" />
            Mahasiswa Magang
        </span>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-6">
            <section class="card overflow-hidden">
                <div class="card-body">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div class="relative shrink-0">
                            <span class="inline-flex rounded-full bg-white p-1.5 shadow-lg shadow-slate-900/10 ring-1 ring-slate-200/70">
                                <x-user-avatar :user="$user" size="2xl" />
                            </span>
                            @if ($user->is_active)
                                <span class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full border-4 border-white bg-emerald-500 text-white">
                                    <x-icon name="check-circle" class="h-3.5 w-3.5" />
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ $user->name }}</h2>
                                @if ($user->is_active)
                                    <span class="badge-green"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif</span>
                                @else
                                    <span class="badge-red">Nonaktif</span>
                                @endif
                            </div>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $user->email }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                @if ($user->nim)
                                    <span class="badge-slate">NIM {{ $user->nim }}</span>
                                @endif
                                @if ($user->phone)
                                    <span class="badge-slate">{{ $user->phone }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-slate-100 pt-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Foto profil</p>
                                <p class="text-xs text-slate-500">JPG, PNG, atau WebP. Maksimal 2MB.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <label class="btn-secondary cursor-pointer">
                                    <x-icon name="camera" class="h-4 w-4" />
                                    Ganti foto
                                    <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                </label>

                                @if ($photo)
                                    <button type="button" wire:click="updatePhoto" wire:loading.attr="disabled" wire:target="updatePhoto" class="btn-primary">
                                        <span wire:loading.remove wire:target="updatePhoto">Simpan foto</span>
                                        <span wire:loading wire:target="updatePhoto">Menyimpan...</span>
                                    </button>
                                @endif

                                @if ($user->photo_path)
                                    <button
                                        type="button"
                                        wire:click="removePhoto"
                                        wire:confirm="Hapus foto profil Anda?"
                                        wire:loading.attr="disabled"
                                        wire:target="removePhoto"
                                        class="btn-ghost text-rose-600 hover:bg-rose-50"
                                    >
                                        Hapus
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($photo)
                            @php
                                $photoPreviewable = in_array($photo->extension(), ['jpg', 'jpeg', 'png', 'webp'], true);
                            @endphp
                            <div class="mt-4 flex items-center gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                @if ($photoPreviewable)
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto profil" class="h-14 w-14 rounded-full object-cover ring-2 ring-white">
                                @else
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-500 ring-2 ring-white">
                                        <x-icon name="alert" class="h-5 w-5" />
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $photo->getClientOriginalName() }}</p>
                                    <p class="text-xs text-slate-500">{{ $photoPreviewable ? 'Siap disimpan sebagai foto profil.' : 'Format file tidak didukung.' }}</p>
                                </div>
                            </div>
                        @endif

                        <p wire:loading wire:target="photo" class="mt-2 text-xs text-slate-500">Mengunggah foto...</p>
                        @error('photo') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <p class="text-lg font-bold text-slate-900">{{ $attendanceCount }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Absensi</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <p class="text-lg font-bold text-slate-900">{{ $logbookProgress['total'] }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Logbook</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <p class="text-lg font-bold text-emerald-600">{{ $logbookProgress['percentage'] === null ? '—' : $logbookProgress['percentage'].'%' }}</p>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Progres</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Informasi Akun</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Perbarui nama, email, dan nomor telepon Anda.</p>
                    </div>
                    <span class="stat-icon bg-brand-50 text-brand-600"><x-icon name="user" class="h-5 w-5" /></span>
                </div>

                <form wire:submit="updateProfile" class="card-body space-y-5">
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

                    <div class="flex justify-end border-t border-slate-100 pt-5">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile" class="btn-primary">
                            <span wire:loading.remove wire:target="updateProfile">Simpan Perubahan</span>
                            <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Keamanan Akun</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Gunakan password yang kuat dan unik.</p>
                    </div>
                    <span class="stat-icon bg-violet-50 text-violet-600"><x-icon name="lock" class="h-5 w-5" /></span>
                </div>

                <form wire:submit="updatePassword" class="card-body space-y-5">
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

                    <div class="flex justify-end border-t border-slate-100 pt-5">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="btn-primary">
                            <x-icon name="shield" class="h-4 w-4" />
                            <span wire:loading.remove wire:target="updatePassword">Perbarui Password</span>
                            <span wire:loading wire:target="updatePassword">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Data Magang</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Dikelola oleh admin magang.</p>
                    </div>
                    <span class="stat-icon bg-accent-50 text-accent-600"><x-icon name="academic-cap" class="h-5 w-5" /></span>
                </div>

                @if ($profile)
                    <div class="card-body space-y-5">
                        <div class="rounded-2xl bg-gradient-to-br from-brand-50 via-white to-accent-50/60 p-4 ring-1 ring-brand-100/70">
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="font-semibold text-brand-700">Progres magang</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-brand-700 shadow-sm">{{ $profile->progressPercent() }}%</span>
                            </div>
                            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white/80 ring-1 ring-brand-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" style="width: {{ $profile->progressPercent() }}%"></div>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">
                                {{ $profile->start_date?->translatedFormat('d M Y') }} — {{ $profile->end_date?->translatedFormat('d M Y') }}
                                · {{ $profile->remainingDays() }} hari tersisa
                            </p>
                        </div>

                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50/70 p-3">
                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                    <x-icon name="building" class="h-3.5 w-3.5" />
                                    Institusi
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $profile->institution }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50/70 p-3">
                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                    <x-icon name="academic-cap" class="h-3.5 w-3.5" />
                                    Jurusan
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $profile->major }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50/70 p-3">
                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                    <x-icon name="book" class="h-3.5 w-3.5" />
                                    Semester
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $profile->semester ?: '—' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50/70 p-3">
                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                    <x-icon name="user" class="h-3.5 w-3.5" />
                                    Pembimbing Lapangan
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $profile->field_supervisor ?: '—' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50/70 p-3 sm:col-span-2">
                                <dt class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                    <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                    Kontak Pembimbing
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $profile->supervisor_phone ?: '—' }}</dd>
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
            </section>
        </div>
    </div>
</div>
