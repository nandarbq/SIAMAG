<div class="space-y-6">
    <section x-data="{ open: false }" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-card">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_400px]">
            <div class="relative overflow-hidden bg-linear-to-br from-white via-white to-brand-50/70 p-6 sm:p-8">
                <div class="pointer-events-none absolute -right-24 top-1/2 hidden h-80 w-80 -translate-y-1/2 rounded-full border border-brand-100/70 sm:block"></div>
                <x-banyuwangi-ornament variant="gandrung" class="pointer-events-none absolute -right-4 bottom-0 hidden h-full w-auto select-none opacity-[0.08] sm:block" />

                <div class="relative">
                    <p class="page-eyebrow">Portal Admin Magang</p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        {{ $greeting }}, {{ auth()->user()->name }}.
                    </h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                        Pantau kehadiran, monitoring logbook, dan kelola data mahasiswa magang
                        <span class="font-semibold text-slate-800">{{ $officeName }}</span> dalam satu tampilan.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="badge-slate">
                            <x-icon name="building" class="h-3.5 w-3.5" />
                            {{ $officeName }}
                        </span>
                        <span class="badge-slate">
                            <x-icon name="calendar" class="h-3.5 w-3.5" />
                            {{ now()->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @if (! $isWorkday)
                            <span class="badge-blue">{{ $nonWorkingLabel ?? 'Hari libur' }}</span>
                        @else
                            <span class="badge-green">Hari kerja · {{ $workStart }} — {{ $workEnd }} WIB</span>
                        @endif
                        <a href="{{ route('admin.students.create') }}" class="btn-primary">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah Mahasiswa
                        </a>
                        <a href="{{ route('admin.reports') }}" class="btn-secondary">
                            <x-icon name="chart" class="h-4 w-4" />
                            Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden bg-brand-950 p-6 text-white sm:p-7">
                <x-banyuwangi-ornament variant="gajah-oling" class="pointer-events-none absolute inset-0 h-full w-full select-none opacity-[0.10]" />

                <div class="relative">
                    @if ($announcements->isNotEmpty())
                        @php
                            $latestAnnouncement = $announcements->first();
                            $isLatestNew = $latestAnnouncement->published_at?->gte(now()->subDays(7)) ?? false;
                        @endphp

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-accent-300 ring-1 ring-inset ring-white/15">
                                    <x-icon name="megaphone" class="h-5 w-5" />
                                </span>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-200">Pengumuman terbaru</p>
                            </div>
                            <a href="{{ route('admin.announcements') }}" class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                                Kelola
                            </a>
                        </div>

                        <div class="mt-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($isLatestNew)
                                    <span class="badge-amber badge-compact">Terbaru</span>
                                @endif
                                <p class="text-[11px] font-medium text-slate-400">{{ $latestAnnouncement->published_at?->translatedFormat('d F Y') }}</p>
                            </div>
                            <h2 class="mt-2 text-lg font-bold tracking-tight text-white sm:text-xl">{{ $latestAnnouncement->title }}</h2>
                            <p class="mt-1.5 line-clamp-3 text-sm leading-relaxed text-slate-300">{{ $latestAnnouncement->body }}</p>
                        </div>

                        @if ($announcements->count() > 1)
                            <button
                                type="button"
                                @click="open = !open"
                                :aria-expanded="open"
                                class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                            >
                                <span x-text="open ? 'Tutup' : 'Lihat semua'"></span>
                                <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-slate-300">{{ $announcements->count() }}</span>
                                <x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                            </button>

                            <div x-cloak x-show="open" x-transition class="mt-4 divide-y divide-white/10 border-t border-white/10">
                                @foreach ($announcements->skip(1) as $announcement)
                                    <div wire:key="announcement-{{ $announcement->id }}" class="py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-white">{{ $announcement->title }}</p>
                                            <span class="text-[11px] text-slate-400">{{ $announcement->published_at?->translatedFormat('d M Y') }}</span>
                                        </div>
                                        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-400">{{ $announcement->body }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="my-5 border-t border-white/10"></div>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-accent-300 ring-1 ring-inset ring-white/15">
                                <x-icon name="check-circle" class="h-5 w-5" />
                            </span>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-200">Fokus hari ini</p>
                        </div>
                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-300 ring-1 ring-inset ring-white/15">
                            {{ now()->translatedFormat('d M') }}
                        </span>
                    </div>

                    @php
                        $focusItems = [
                            ['label' => 'Mahasiswa Aktif', 'value' => number_format($totalStudents).' mahasiswa', 'hint' => 'Periode berjalan', 'icon' => 'users', 'tone' => 'bg-brand-400/15 text-brand-300 ring-brand-300/20'],
                            ['label' => 'Hadir Hari Ini', 'value' => number_format($present).' hadir', 'hint' => $late.' terlambat', 'icon' => 'check-circle', 'tone' => 'bg-emerald-400/15 text-emerald-300 ring-emerald-300/20'],
                            ['label' => 'Izin / Sakit', 'value' => number_format($permit).' tercatat', 'hint' => 'Hari ini', 'icon' => 'calendar', 'tone' => 'bg-amber-400/15 text-amber-300 ring-amber-300/20'],
                            ['label' => 'Belum Absen', 'value' => number_format($alpha).' mahasiswa', 'hint' => $isWorkday ? 'Perlu ditindaklanjuti' : 'Hari libur', 'icon' => 'clock', 'tone' => 'bg-rose-400/15 text-rose-300 ring-rose-300/20'],
                        ];
                    @endphp

                    <div class="mt-4">
                        <h2 class="text-lg font-bold tracking-tight text-white sm:text-xl">
                            {{ $isWorkday ? 'Ringkasan kehadiran hari ini.' : ($nonWorkingLabel ?? 'Hari ini libur').'.' }}
                        </h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-300">
                            {{ $isWorkday ? 'Pastikan seluruh mahasiswa magang tercatat kehadirannya hari ini.' : 'Tidak ada kewajiban absensi hari ini. Pantau rekap pada hari kerja berikutnya.' }}
                        </p>
                    </div>

                    <div class="mt-5 space-y-2.5">
                        @foreach ($focusItems as $item)
                            <div class="flex flex-wrap items-start gap-3 rounded-xl bg-white/5 p-3 ring-1 ring-inset ring-white/10 sm:flex-nowrap sm:items-center">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset {{ $item['tone'] }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</p>
                                    <p class="mt-0.5 truncate text-sm font-semibold text-white">{{ $item['value'] }}</p>
                                </div>
                                <p class="w-full pl-12 text-left text-[11px] text-slate-400 sm:w-auto sm:max-w-[7rem] sm:pl-0 sm:text-right">{{ $item['hint'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('admin.attendances') }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                        Pantau Absensi
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Mahasiswa Aktif', 'value' => $totalStudents, 'icon' => 'users', 'tone' => 'bg-brand-50 text-brand-600', 'hint' => 'Terdaftar di sistem'],
                ['label' => 'Hadir Hari Ini', 'value' => $present, 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600', 'hint' => $late.' terlambat'],
                ['label' => 'Izin / Sakit', 'value' => $permit, 'icon' => 'calendar', 'tone' => 'bg-amber-50 text-amber-600', 'hint' => 'Hari ini'],
                ['label' => 'Belum Absen', 'value' => $alpha, 'icon' => 'clock', 'tone' => 'bg-rose-50 text-rose-600', 'hint' => 'Perlu ditindaklanjuti'],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="card card-body animate-fade-up">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $stat['hint'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $stat['tone'] }}">
                        <x-icon :name="$stat['icon']" class="h-4 w-4" />
                    </span>
                </div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Tren Kehadiran</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Rekap 7 hari terakhir</p>
                </div>
                <a href="{{ route('admin.attendances') }}" class="btn-secondary btn-sm">
                    Detail
                    <x-icon name="arrow-up-circle" class="h-3.5 w-3.5" />
                </a>
            </div>
            <div class="card-body">
                <div class="h-72" wire:ignore x-data="{
                    chart: null,
                    init() {
                        const data = JSON.parse(this.$el.dataset.chart);
                        const style = getComputedStyle(document.documentElement);
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    { label: 'Hadir', data: data.present, backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 22 },
                                    { label: 'Terlambat', data: data.late, backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 22 },
                                    { label: 'Izin / Sakit', data: data.permit, backgroundColor: '#3b82f6', borderRadius: 6, maxBarThickness: 22 },
                                    { label: 'Belum Absen', data: data.alpha, backgroundColor: '#f43f5e', borderRadius: 6, maxBarThickness: 22 },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16, font: { size: 11, family: 'Instrument Sans' } } },
                                    tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 12, titleFont: { family: 'Instrument Sans' }, bodyFont: { family: 'Instrument Sans' } },
                                },
                                scales: {
                                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10, family: 'Instrument Sans' }, color: '#64748b' } },
                                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 10, family: 'Instrument Sans' }, color: '#64748b' }, grid: { color: 'rgba(148,163,184,0.14)' } },
                                },
                            },
                        });
                    },
                }" data-chart='@json($chart)'>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        <div class="card self-start">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Monitoring</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Izin, sakit, dan kehadiran hari ini</p>
                </div>
                <span class="badge-amber">{{ $permit }} item</span>
            </div>
            <div class="divide-y divide-slate-100">
                <a href="{{ route('admin.leaves') }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50">
                    <span class="stat-icon bg-brand-50 text-brand-600">
                        <x-icon name="calendar" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">Izin & Sakit Hari Ini</p>
                        <p class="text-xs text-slate-500">{{ $permit }} mahasiswa tercatat izin atau sakit</p>
                    </div>
                    <x-icon name="chevron-down" class="h-4 w-4 -rotate-90 text-slate-400" />
                </a>
                <a href="{{ route('admin.leaves') }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50">
                    <span class="stat-icon bg-amber-50 text-amber-600">
                        <x-icon name="clock" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">Sakit Berlangsung</p>
                        <p class="text-xs text-slate-500">{{ $ongoingSick }} pengajuan sakit belum ditutup</p>
                    </div>
                    <x-icon name="chevron-down" class="h-4 w-4 -rotate-90 text-slate-400" />
                </a>
                <a href="{{ route('admin.attendances') }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50">
                    <span class="stat-icon bg-rose-50 text-rose-600">
                        <x-icon name="clock" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">Belum Absen</p>
                        <p class="text-xs text-slate-500">{{ $alpha }} mahasiswa belum tercatat hari ini</p>
                    </div>
                    <x-icon name="chevron-down" class="h-4 w-4 -rotate-90 text-slate-400" />
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Absensi Terbaru</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Aktivitas kehadiran terakhir</p>
                </div>
                <a href="{{ route('admin.attendances') }}" class="btn-secondary btn-sm">Semua</a>
            </div>
            <x-table-scroll-hint />
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60">
                        <tr>
                            <th class="table-th">Mahasiswa</th>
                            <th class="table-th">Tanggal</th>
                            <th class="table-th">Masuk</th>
                            <th class="table-th">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentAttendances as $attendance)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="table-td">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-700">
                                            {{ strtoupper(substr($attendance->user?->name ?? '?', 0, 1)) }}
                                        </span>
                                        <span class="font-semibold text-slate-800">{{ $attendance->user?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-td">{{ $attendance->date?->translatedFormat('d M Y') }}</td>
                                <td class="table-td">{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</td>
                                <td class="table-td">
                                    <x-attendance-status-admin :status="$attendance->status" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data absensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Logbook Terbaru</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Aktivitas terakhir mahasiswa</p>
                </div>
                <a href="{{ route('admin.logbooks') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat</a>
            </div>
            <div class="card-body">
                @forelse ($recentLogbooks as $logbook)
                    <div wire:key="logbook-{{ $logbook->id }}" class="relative border-l border-slate-200 pb-5 pl-6 last:border-transparent last:pb-0">
                        <span class="absolute -left-1.5 top-0 flex h-3 w-3 rounded-full border-2 border-white bg-brand-500"></span>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="min-w-0 flex-1 line-clamp-2 text-sm font-semibold text-slate-800">{{ $logbook->activity }}</p>
                            <span class="badge-slate badge-compact">{{ $logbook->date?->translatedFormat('d M') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $logbook->user?->name ?? '-' }} · {{ $logbook->date?->translatedFormat('l') }}</p>
                    </div>
                @empty
                    <p class="empty-state">Belum ada logbook.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
