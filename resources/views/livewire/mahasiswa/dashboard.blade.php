<div class="space-y-6">
    <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
            <div class="flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                <p class="page-eyebrow">Dashboard Magang</p>
            </div>
            <h1 class="page-title mt-2">{{ $greeting }}, {{ auth()->user()->name }}</h1>
            <p class="page-subtitle flex flex-wrap items-center gap-x-1.5 gap-y-1">
                <x-icon name="building" class="h-4 w-4 text-slate-400" />
                <span>Magang di <span class="font-semibold text-slate-700">{{ $officeName }}</span></span>
                @if ($profile?->institution)
                    <span class="text-slate-300">·</span>
                    <span>{{ $profile->institution }}</span>
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge-slate sm:hidden">
                <x-icon name="calendar" class="h-3.5 w-3.5" />
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
            @if (! $isWorkday)
                <span class="badge-blue">Libur</span>
            @elseif ($today)
                <x-attendance-status :status="$today->status" />
            @else
                <span class="badge-amber">Belum absen</span>
            @endif
            <a href="{{ route('mahasiswa.attendance') }}" class="btn-primary">
                <x-icon name="camera" class="h-4 w-4" />
                {{ $today?->check_in_at ? 'Buka Absensi' : 'Absen Sekarang' }}
            </a>
        </div>
    </section>

    @if ($announcements->isNotEmpty())
        @php
            $latestAnnouncement = $announcements->first();
            $isLatestNew = $latestAnnouncement->published_at?->gte(now()->subDays(7)) ?? false;
        @endphp
        <section x-data="{ open: false }" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-card">
            <div class="grid lg:grid-cols-[230px_minmax(0,1fr)]">
                <div class="relative overflow-hidden bg-linear-to-br from-brand-700 via-brand-800 to-brand-950 p-5 text-white sm:p-6">
                    <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;"></div>
                    <div class="relative flex h-full flex-col justify-between gap-4 sm:gap-6">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-accent-300 ring-1 ring-inset ring-white/15">
                                <x-icon name="megaphone" class="h-5 w-5" />
                            </span>
                            @if ($announcements->count() > 1)
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-inset ring-white/15">
                                    {{ $announcements->count() }} info
                                </span>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-200">Pusat Informasi</p>
                            <p class="mt-1 text-2xl font-bold leading-tight">Pengumuman terbaru</p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($isLatestNew)
                                    <span class="badge-amber badge-compact">Terbaru</span>
                                @endif
                                <p class="text-[11px] font-medium text-slate-400">{{ $latestAnnouncement->published_at?->translatedFormat('d F Y') }}</p>
                            </div>
                            <h2 class="mt-2 text-lg font-bold tracking-tight text-slate-900 sm:text-xl">{{ $latestAnnouncement->title }}</h2>
                            <p class="mt-1.5 line-clamp-3 max-w-3xl text-sm leading-relaxed text-slate-600">{{ $latestAnnouncement->body }}</p>
                        </div>

                        @if ($announcements->count() > 1)
                            <button
                                type="button"
                                @click="open = !open"
                                :aria-expanded="open"
                                class="btn-secondary btn-sm shrink-0 self-start"
                            >
                                <span x-text="open ? 'Tutup' : 'Lihat semua'"></span>
                                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $announcements->count() }}</span>
                                <x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                            </button>
                        @endif
                    </div>

                    @if ($announcements->count() > 1)
                        <div x-cloak x-show="open" x-transition class="mt-4 divide-y divide-slate-100 border-t border-slate-100">
                            @foreach ($announcements->skip(1) as $announcement)
                                <div wire:key="announcement-{{ $announcement->id }}" class="py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-800">{{ $announcement->title }}</p>
                                        <span class="text-[11px] text-slate-400">{{ $announcement->published_at?->translatedFormat('d M Y') }}</span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $announcement->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if (! $profile)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Profil magang belum lengkap. Hubungi admin untuk melengkapi institusi, jurusan, dan periode magang sebelum melakukan absensi.</p>
        </div>
    @endif

    <section class="grid gap-4 lg:grid-cols-3">
        <div class="card card-body">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Absensi Hari Ini</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $isWorkday ? 'Jam kerja '.$workStart.' — '.$workEnd.' WIB' : ($nonWorkingLabel ?? 'Hari libur') }}
                    </p>
                </div>
                @if (! $isWorkday)
                    <span class="badge-blue">Libur</span>
                @elseif ($today)
                    <x-attendance-status :status="$today->status" />
                @else
                    <span class="badge-amber">Belum absen</span>
                @endif
            </div>

            @if (! $isWorkday)
                <div class="mt-4 rounded-xl bg-sky-50 p-4 ring-1 ring-inset ring-sky-100">
                    <p class="text-sm font-semibold text-sky-900">{{ $todayHoliday ? 'Libur: '.$todayHoliday->name : 'Hari ini libur' }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-sky-700">
                        {{ $todayHoliday ? 'Hari ini merupakan hari libur. Tidak perlu melakukan absensi.' : $nonWorkingLabel.'. Tidak perlu melakukan absensi.' }}
                    </p>
                </div>
            @else
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-inset ring-slate-100">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Masuk</p>
                        <p class="mt-1 text-xl font-bold text-slate-900">{{ $today?->check_in_at?->format('H:i') ?? '--:--' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-inset ring-slate-100">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Pulang</p>
                        <p class="mt-1 text-xl font-bold text-slate-900">{{ $today?->check_out_at?->format('H:i') ?? '--:--' }}</p>
                    </div>
                </div>
                <a href="{{ route('mahasiswa.attendance') }}" class="btn-secondary mt-4 w-full">
                    <x-icon name="camera" class="h-4 w-4" />
                    {{ $today?->check_in_at ? 'Buka Absensi' : 'Absen Sekarang' }}
                </a>
            @endif
        </div>

        <div class="card card-body">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progres Magang</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $profile?->major ?? 'Data magang' }}</p>
                </div>
                @if ($profile)
                    <span class="badge-slate">{{ $profile->semester ? 'Semester '.$profile->semester : 'Magang' }}</span>
                @endif
            </div>

            @if ($profile)
                <div class="mt-4 flex items-end justify-between gap-3">
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ $profile->progressPercent() }}%</p>
                    <p class="text-xs font-medium text-slate-500">{{ $profile->remainingDays() }} hari tersisa</p>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $profile->progressPercent() }}%"></div>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    {{ $profile->start_date?->translatedFormat('d M Y') ?? '-' }} — {{ $profile->end_date?->translatedFormat('d M Y') ?? '-' }}
                </p>
            @else
                <p class="mt-4 text-sm leading-relaxed text-slate-500">Lengkapi profil magang untuk melihat progres dan periode magang.</p>
            @endif
        </div>

        <div class="card card-body">
            @php
                $logbookBadge = match ($logbookProgress['today_status']) {
                    'filled' => ['badge-green', 'Sudah diisi'],
                    'missing' => ['badge-red', 'Belum diisi'],
                    'holiday' => ['badge-blue', 'Libur'],
                    'excused' => ['badge-blue', 'Izin/Sakit'],
                    'not_attended' => ['badge-amber', 'Belum absen'],
                    default => ['badge-slate', 'Belum ada data'],
                };
            @endphp

            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progres Logbook</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $logbookProgress['missing'] > 0 ? $logbookProgress['missing'].' hari tertinggal' : 'Semua logbook lengkap' }}
                    </p>
                </div>
                <span class="{{ $logbookBadge[0] }}">{{ $logbookBadge[1] }}</span>
            </div>

            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">
                {{ $logbookProgress['percentage'] === null ? '—' : $logbookProgress['percentage'].'%' }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ $logbookProgress['filled'] }} dari {{ $logbookProgress['expected'] }} hari kerja</p>
            <a href="{{ route('mahasiswa.logbooks') }}" class="btn-secondary mt-4 w-full">Buka Logbook</a>
        </div>
    </section>

    @if ($logbookProgress['today_status'] === 'missing')
        <div class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 sm:flex-row sm:items-center">
            <div class="flex flex-1 items-start gap-3">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                <p>Logbook hari ini belum diisi. Lengkapi sebelum hari berganti.</p>
            </div>
            <a href="{{ route('mahasiswa.logbooks') }}" class="btn-secondary btn-sm self-start sm:self-auto">Isi sekarang</a>
        </div>
    @elseif ($logbookProgress['missing'] > 0)
        <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 sm:flex-row sm:items-center">
            <div class="flex flex-1 items-start gap-3">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                <p>Ada {{ $logbookProgress['missing'] }} hari kerja yang belum diisi logbook.</p>
            </div>
            <a href="{{ route('mahasiswa.logbooks') }}" class="btn-secondary btn-sm self-start sm:self-auto">Lengkapi</a>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-3">
        @php
            $stats = [
                ['label' => 'Hadir Bulan Ini', 'value' => $hadir, 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600'],
                ['label' => 'Terlambat', 'value' => $terlambat, 'icon' => 'clock', 'tone' => 'bg-amber-50 text-amber-600'],
                ['label' => 'Izin / Sakit', 'value' => $izinSakit, 'icon' => 'calendar', 'tone' => 'bg-brand-50 text-brand-600'],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="card card-body">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $stat['tone'] }}">
                        <x-icon :name="$stat['icon']" class="h-4 w-4" />
                    </span>
                </div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Aktivitas 7 Hari Terakhir</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Status kehadiran harian Anda</p>
                </div>
                <a href="{{ route('mahasiswa.attendance') }}" class="btn-secondary btn-sm">Riwayat</a>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
                    @foreach ($week as $day)
                        <div
                            wire:key="week-day-{{ $day['date']->toDateString() }}"
                            @class([
                                'min-w-0 rounded-xl border p-2 text-center sm:p-3',
                                'border-slate-200 bg-white' => ! $day['isToday'],
                                'border-brand-300 bg-brand-50/50 ring-2 ring-brand-500/10' => $day['isToday'],
                            ])
                        >
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $day['day_label'] }}</p>
                            <p class="mt-0.5 text-base font-bold text-slate-900">{{ $day['day'] }}</p>
                            <span class="mx-auto mt-2 flex h-8 w-8 items-center justify-center rounded-full ring-1 ring-inset {{ $day['tone'] }}">
                                <x-icon :name="$day['icon']" class="h-4 w-4" />
                            </span>
                            <p class="mt-1.5 text-[10px] font-semibold text-slate-500">{{ $day['status_label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-[11px] font-medium text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Hadir</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Telat</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-500"></span>Izin / Sakit</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Libur</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Belum ada data</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Logbook Terbaru</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        @if ($logbookProgress['missing'] > 0)
                            {{ $logbookProgress['missing'] }} hari kerja belum diisi
                        @else
                            Semua logbook sudah lengkap
                        @endif
                    </p>
                </div>
                <a href="{{ route('mahasiswa.logbooks') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Buka</a>
            </div>
            <div class="card-body">
                @forelse ($recentLogbooks as $logbook)
                    <div wire:key="logbook-{{ $logbook->id }}" class="relative border-l border-slate-200 pb-5 pl-6 last:border-transparent last:pb-0">
                        <span class="absolute -left-1.5 top-0 flex h-3 w-3 rounded-full border-2 border-white bg-brand-500"></span>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="min-w-0 flex-1 line-clamp-2 text-sm font-semibold text-slate-800">{{ $logbook->activity }}</p>
                            <span class="badge-slate badge-compact">{{ $logbook->date?->translatedFormat('d M') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $logbook->date?->translatedFormat('l') }}</p>
                    </div>
                @empty
                    <p class="empty-state">Belum ada logbook.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
