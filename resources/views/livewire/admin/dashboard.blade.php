<div class="space-y-6">
    <section x-data="{ open: false }" class="relative overflow-hidden rounded-3xl bg-brand-950 shadow-card">
        <x-banyuwangi-ornament variant="gajah-oling" class="pointer-events-none absolute inset-0 h-full w-full select-none opacity-[0.08]" />
        <x-banyuwangi-ornament variant="gandrung" class="pointer-events-none absolute -right-8 bottom-0 hidden h-[26rem] w-auto select-none opacity-[0.12] lg:block" />

        <div class="relative p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 max-w-2xl">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-300">Pusat kendali · Portal Admin Magang</p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                        {{ $greeting }}, {{ auth()->user()->name }}.
                    </h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">
                        Kelola data magang, pantau kehadiran, dan tinjau laporan
                        <span class="font-semibold text-white">{{ $officeName }}</span> dari satu tempat.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-inset ring-white/15">
                        <x-icon name="building" class="h-3.5 w-3.5" />
                        {{ $officeName }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-inset ring-white/15">
                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                        {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                    @if (! $isWorkday)
                        <span class="badge-blue">{{ $nonWorkingLabel ?? 'Hari libur' }}</span>
                    @else
                        <span class="badge-green">Hari kerja · {{ $workStart }} — {{ $workEnd }} WIB</span>
                    @endif
                </div>
            </div>

            @php
                $quickActions = [
                    ['label' => 'Tambah Mahasiswa', 'hint' => 'Daftarkan mahasiswa baru', 'icon' => 'plus', 'route' => route('admin.students.create')],
                    ['label' => 'Kelola Kelompok', 'hint' => 'Atur kelompok & pembimbing', 'icon' => 'users', 'route' => route('admin.groups')],
                    ['label' => 'Pantau Absensi', 'hint' => 'Cek kehadiran hari ini', 'icon' => 'camera', 'route' => route('admin.attendances')],
                    ['label' => 'Lihat Laporan', 'hint' => 'Rekap & ekspor data', 'icon' => 'chart', 'route' => route('admin.reports')],
                ];
            @endphp

            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="group flex items-center gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-inset ring-white/10 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-brand-200 ring-1 ring-inset ring-white/15">
                            <x-icon :name="$action['icon']" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-white">{{ $action['label'] }}</span>
                            <span class="mt-0.5 block truncate text-[11px] text-slate-400">{{ $action['hint'] }}</span>
                        </span>
                        <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-500 transition group-hover:translate-x-0.5 group-hover:text-white" />
                    </a>
                @endforeach
            </div>

            @if ($announcements->isNotEmpty())
                @php
                    $latestAnnouncement = $announcements->first();
                    $isLatestNew = $latestAnnouncement->published_at?->gte(now()->subDays(7)) ?? false;
                @endphp

                <div class="mt-6 rounded-2xl bg-white/5 p-4 ring-1 ring-inset ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-accent-300 ring-1 ring-inset ring-white/15">
                                <x-icon name="megaphone" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand-200">Pengumuman terbaru</p>
                                    @if ($isLatestNew)
                                        <span class="badge-amber badge-compact">Terbaru</span>
                                    @endif
                                    <span class="text-[11px] text-slate-400">{{ $latestAnnouncement->published_at?->translatedFormat('d F Y') }}</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-white">{{ $latestAnnouncement->title }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($announcements->count() > 1)
                                <button
                                    type="button"
                                    @click="open = !open"
                                    :aria-expanded="open"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                                >
                                    <span x-text="open ? 'Tutup' : 'Lihat lainnya'"></span>
                                    <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-slate-300">{{ $announcements->count() }}</span>
                                    <x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                                </button>
                            @endif
                            <a href="{{ route('admin.announcements') }}" class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-900 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                                Kelola
                            </a>
                        </div>
                    </div>

                    <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-300">{{ $latestAnnouncement->body }}</p>

                    @if ($announcements->count() > 1)
                        <div x-cloak x-show="open" x-transition class="mt-3 divide-y divide-white/10 border-t border-white/10">
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
                </div>
            @endif
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
