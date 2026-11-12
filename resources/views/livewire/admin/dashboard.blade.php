<div class="space-y-6">
    <section class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-card sm:p-8">
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-accent-500/20 blur-3xl"></div>
        <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <div>
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Ringkasan Operasional
                </p>
                <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ auth()->user()->name }}.</h1>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-300">
                    Pantau kehadiran, monitoring logbook, dan kelola data mahasiswa magang BMKG Banyuwangi dalam satu tampilan.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.students.create') }}" class="btn bg-white text-slate-900 hover:bg-slate-100">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Mahasiswa
                </a>
                <a href="{{ route('admin.reports') }}" class="btn glass text-white hover:bg-white/20">
                    <x-icon name="chart" class="h-4 w-4" />
                    Lihat Laporan
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Mahasiswa Aktif', 'value' => $totalStudents, 'icon' => 'users', 'tone' => 'brand', 'hint' => 'Terdaftar di sistem'],
                ['label' => 'Hadir Hari Ini', 'value' => $present, 'icon' => 'check-circle', 'tone' => 'emerald', 'hint' => $late.' terlambat'],
                ['label' => 'Izin / Sakit', 'value' => $permit, 'icon' => 'calendar', 'tone' => 'amber', 'hint' => 'Hari ini'],
                ['label' => 'Belum Absen', 'value' => $alpha, 'icon' => 'clock', 'tone' => 'rose', 'hint' => 'Perlu ditindaklanjuti'],
            ];
            $tones = [
                'brand' => 'bg-brand-50 text-brand-600',
                'emerald' => 'bg-emerald-50 text-emerald-600',
                'amber' => 'bg-amber-50 text-amber-600',
                'rose' => 'bg-rose-50 text-rose-600',
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="card card-body animate-fade-up">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $stat['hint'] }}</p>
                    </div>
                    <span class="stat-icon {{ $tones[$stat['tone']] }}">
                        <x-icon :name="$stat['icon']" class="h-5 w-5" />
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

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Perlu Tindakan</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Antrian persetujuan izin & kehadiran</p>
                    </div>
                    <span class="badge-amber">{{ $pendingLeaves }} item</span>
                </div>
                <div class="divide-y divide-slate-100">
                    <a href="{{ route('admin.leaves') }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50">
                        <span class="stat-icon bg-brand-50 text-brand-600">
                            <x-icon name="calendar" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800">Persetujuan Izin</p>
                            <p class="text-xs text-slate-500">{{ $pendingLeaves }} pengajuan menunggu keputusan</p>
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

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengumuman</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Informasi terbaru</p>
                    </div>
                    <a href="{{ route('admin.announcements') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Kelola</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($announcements as $announcement)
                        <div class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">{{ $announcement->title }}</p>
                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $announcement->body }}</p>
                            <p class="mt-2 text-[11px] text-slate-400">{{ $announcement->published_at?->translatedFormat('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="empty-state">Belum ada pengumuman.</p>
                    @endforelse
                </div>
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
                                    <x-attendance-status :status="$attendance->status" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state">Belum ada data absensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Logbook Terbaru</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Aktivitas terakhir mahasiswa</p>
                </div>
                <a href="{{ route('admin.logbooks') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentLogbooks as $logbook)
                    <div class="flex items-start gap-3 px-5 py-4">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <x-icon name="book" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $logbook->user?->name ?? '-' }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $logbook->activity }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $logbook->date?->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="empty-state">Belum ada logbook.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
