<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.students') }}" class="btn-secondary mt-0.5 px-2.5 py-2">
                <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
            </a>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Profil Mahasiswa</p>
                <h1 class="page-title mt-1">{{ $student->name }}</h1>
                <p class="page-subtitle">{{ $student->email }} · NIM {{ $student->nim ?: '-' }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="toggleActive" class="btn-secondary">
                <x-icon :name="$student->is_active ? 'lock' : 'check-circle'" class="h-4 w-4" />
                {{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
            </button>
            <a href="{{ route('admin.students.edit', $student) }}" class="btn-primary">
                <x-icon name="pencil" class="h-4 w-4" />
                Edit Profil
            </a>
        </div>
    </div>

    <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="card overflow-hidden">
            <div class="relative h-28 bg-slate-900">
                <div class="absolute inset-0 bg-[radial-gradient(500px_180px_at_20%_0%,rgba(59,130,246,0.5),transparent_70%)]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(400px_160px_at_90%_100%,rgba(245,158,11,0.25),transparent_70%)]"></div>
            </div>
            <div class="relative px-5 pb-5">
                <div class="-mt-10 flex items-end justify-between gap-4">
                    <span class="flex h-20 w-20 items-center justify-center rounded-2xl border-4 border-white bg-gradient-to-br from-brand-500 to-brand-700 text-2xl font-bold text-white shadow-lg">
                        {{ $student->initials() }}
                    </span>
                    @if ($student->is_active)
                        <span class="badge-green mb-1">Akun Aktif</span>
                    @else
                        <span class="badge-slate mb-1">Nonaktif</span>
                    @endif
                </div>

                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ $student->name }}</h2>
                <p class="text-sm text-slate-500">{{ $profile?->institution ?? 'Institusi belum diisi' }}</p>

                <div class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Jurusan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profile?->major ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Semester</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profile?->semester ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">No. HP</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $student->phone ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Pembimbing Lapangan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profile?->field_supervisor ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Progres Magang</h2>
                    <p class="text-xs text-slate-500">{{ $profile?->start_date?->translatedFormat('d M Y') ?? '-' }} — {{ $profile?->end_date?->translatedFormat('d M Y') ?? '-' }}</p>
                </div>
                <span class="text-2xl font-bold text-brand-600">{{ $profile?->progressPercent() ?? 0 }}%</span>
            </div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-700" style="width: {{ $profile?->progressPercent() ?? 0 }}%"></div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-emerald-50 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-600">Hadir</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $hadir }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600">Terlambat</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700">{{ $terlambat }}</p>
                </div>
                <div class="rounded-xl bg-brand-50 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-brand-600">Izin / Sakit</p>
                    <p class="mt-1 text-2xl font-bold text-brand-700">{{ $izinSakit }}</p>
                </div>
                <div class="rounded-xl bg-violet-50 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-violet-600">Progres Logbook</p>
                    <p class="mt-1 text-2xl font-bold text-violet-700">{{ $logbookProgress['percentage'] === null ? '—' : $logbookProgress['percentage'].'%' }}</p>
                </div>
            </div>

            @if ($profile)
                <div class="mt-5 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-600">
                    <x-icon name="clock" class="h-4 w-4 text-brand-600" />
                    Sisa {{ $profile->remainingDays() }} hari magang
                </div>
            @endif
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Absensi</h2>
                    <p class="mt-0.5 text-xs text-slate-500">10 data terakhir</p>
                </div>
                <a href="{{ route('admin.attendances') }}" class="btn-secondary btn-sm">Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60">
                        <tr>
                            <th class="table-th">Tanggal</th>
                            <th class="table-th">Masuk</th>
                            <th class="table-th">Pulang</th>
                            <th class="table-th">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($attendances as $attendance)
                            <tr>
                                <td class="table-td font-medium text-slate-700">{{ $attendance->date?->translatedFormat('d M Y') }}</td>
                                <td class="table-td">{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</td>
                                <td class="table-td">{{ $attendance->check_out_at?->format('H:i') ?? '-' }}</td>
                                <td class="table-td"><x-attendance-status :status="$attendance->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">Belum ada absensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Logbook Terbaru</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Aktivitas harian</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($logbooks as $logbook)
                        <div class="px-5 py-3.5">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $logbook->activity }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $logbook->date?->translatedFormat('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="empty-state">Belum ada logbook.</p>
                    @endforelse
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengajuan Izin</h2>
                        <p class="mt-0.5 text-xs text-slate-500">5 data terakhir</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($leaves as $leave)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $leave->type->label() }}</p>
                                <p class="text-xs text-slate-400">{{ $leave->start_date?->translatedFormat('d M') }} — {{ $leave->end_date?->translatedFormat('d M Y') }}</p>
                            </div>
                            <x-leave-status :status="$leave->status" />
                        </div>
                    @empty
                        <p class="empty-state">Belum ada pengajuan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="card border-rose-200 bg-rose-50/40">
        <div class="card-body flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-sm font-bold text-rose-800">Hapus data mahasiswa</h2>
                <p class="mt-1 text-xs text-rose-600">Semua absensi, logbook, dan pengajuan terkait akan ikut terhapus permanen.</p>
            </div>
            <button
                type="button"
                wire:click="delete"
                wire:confirm="Yakin ingin menghapus {{ $student->name }} beserta seluruh datanya?"
                class="btn-danger"
            >
                <x-icon name="trash" class="h-4 w-4" />
                Hapus Mahasiswa
            </button>
        </div>
    </section>
</div>
