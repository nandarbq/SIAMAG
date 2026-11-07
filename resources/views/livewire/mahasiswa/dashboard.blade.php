<div class="space-y-6">
    @if (! $profile)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Profil magang belum lengkap. Hubungi admin untuk melengkapi institusi, jurusan, dan periode magang sebelum melakukan absensi.</p>
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-card sm:p-8">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-accent-500/20 blur-3xl"></div>
        <div class="relative grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div>
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-accent-300">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Portal Mahasiswa Magang
                </p>
                <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Halo, {{ auth()->user()->name }}.</h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">
                    Jangan lupa absen masuk, isi logbook harian, dan pantau progres magang Anda di {{ $profile?->institution ?? 'BMKG Banyuwangi' }}.
                </p>

                @if ($profile)
                    <div class="mt-6 max-w-md">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Progres magang</span>
                            <span class="font-bold text-white">{{ $profile->progressPercent() }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-accent-400 to-accent-600" style="width: {{ $profile->progressPercent() }}%"></div>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-400">
                            {{ $profile->start_date?->translatedFormat('d M Y') }} — {{ $profile->end_date?->translatedFormat('d M Y') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-300">Absensi Hari Ini</p>
                    @if ($today)
                        <x-attendance-status :status="$today->status" />
                    @else
                        <span class="badge bg-white/10 text-slate-200 ring-1 ring-inset ring-white/10">Belum absen</span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-950/30 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Masuk</p>
                        <p class="mt-1 text-xl font-bold">{{ $today?->check_in_at?->format('H:i') ?? '--:--' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-950/30 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Pulang</p>
                        <p class="mt-1 text-xl font-bold">{{ $today?->check_out_at?->format('H:i') ?? '--:--' }}</p>
                    </div>
                </div>

                <a href="{{ route('mahasiswa.attendance') }}" class="btn mt-4 w-full bg-white text-slate-900 hover:bg-slate-100">
                    <x-icon name="camera" class="h-4 w-4" />
                    {{ $today?->check_in_at ? 'Buka Absensi' : 'Absen Sekarang' }}
                </a>
                <p class="mt-3 text-center text-[11px] text-slate-400">Jam kerja {{ $workStart }} — {{ $workEnd }} WIB</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Hadir Bulan Ini', 'value' => $hadir, 'icon' => 'check-circle', 'tone' => 'emerald'],
                ['label' => 'Terlambat', 'value' => $terlambat, 'icon' => 'clock', 'tone' => 'amber'],
                ['label' => 'Izin / Sakit', 'value' => $izinSakit, 'icon' => 'calendar', 'tone' => 'brand'],
                ['label' => 'Logbook Disetujui', 'value' => $logbookApproved, 'icon' => 'book', 'tone' => 'violet'],
            ];
            $tones = [
                'emerald' => 'bg-emerald-50 text-emerald-600',
                'amber' => 'bg-amber-50 text-amber-600',
                'brand' => 'bg-brand-50 text-brand-600',
                'violet' => 'bg-violet-50 text-violet-600',
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="card card-body animate-fade-up">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $tones[$stat['tone']] }}">
                        <x-icon :name="$stat['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Aktivitas 7 Hari Terakhir</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Rekap kehadiran harian Anda</p>
                </div>
                <a href="{{ route('mahasiswa.attendance') }}" class="btn-secondary btn-sm">Riwayat</a>
            </div>
            <div class="card-body">
                <div class="h-64" wire:ignore x-data="{
                    chart: null,
                    init() {
                        const data = JSON.parse(this.$el.dataset.chart);
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    { label: 'Hadir', data: data.present, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .4, pointRadius: 3 },
                                    { label: 'Terlambat', data: data.late, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.10)', fill: true, tension: .4, pointRadius: 3 },
                                    { label: 'Izin / Sakit', data: data.permit, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.10)', fill: true, tension: .4, pointRadius: 3 },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16, font: { size: 11, family: 'Instrument Sans' } } },
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#64748b' } },
                                    y: { beginAtZero: true, max: 1, ticks: { stepSize: 1, precision: 0, font: { size: 10 }, color: '#64748b' }, grid: { color: 'rgba(148,163,184,.14)' } },
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
                        <h2 class="text-base font-bold text-slate-900">Logbook Terbaru</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $logbookPending }} perlu dilengkapi</p>
                    </div>
                    <a href="{{ route('mahasiswa.logbooks') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Buka</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentLogbooks as $logbook)
                        <div class="px-5 py-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $logbook->activity }}</p>
                                <x-logbook-status :status="$logbook->status" />
                            </div>
                            <p class="mt-1 text-xs text-slate-400">{{ $logbook->date?->translatedFormat('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="empty-state">Belum ada logbook.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengumuman</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Informasi dari admin</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($announcements as $announcement)
                        <div class="px-5 py-3.5">
                            <p class="text-sm font-semibold text-slate-800">{{ $announcement->title }}</p>
                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $announcement->body }}</p>
                            <p class="mt-1.5 text-[11px] text-slate-400">{{ $announcement->published_at?->translatedFormat('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="empty-state">Belum ada pengumuman.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('mahasiswa.attendance') }}" class="card card-body flex items-center gap-4 transition hover:-translate-y-0.5 hover:shadow-card">
            <span class="stat-icon bg-brand-50 text-brand-600"><x-icon name="camera" class="h-5 w-5" /></span>
            <div>
                <p class="text-sm font-bold text-slate-900">Absensi</p>
                <p class="text-xs text-slate-500">Selfie + validasi GPS</p>
            </div>
        </a>
        <a href="{{ route('mahasiswa.logbooks') }}" class="card card-body flex items-center gap-4 transition hover:-translate-y-0.5 hover:shadow-card">
            <span class="stat-icon bg-amber-50 text-amber-600"><x-icon name="book" class="h-5 w-5" /></span>
            <div>
                <p class="text-sm font-bold text-slate-900">Logbook</p>
                <p class="text-xs text-slate-500">Isi aktivitas harian</p>
            </div>
        </a>
        <a href="{{ route('mahasiswa.leaves') }}" class="card card-body flex items-center gap-4 transition hover:-translate-y-0.5 hover:shadow-card">
            <span class="stat-icon bg-rose-50 text-rose-600"><x-icon name="calendar" class="h-5 w-5" /></span>
            <div>
                <p class="text-sm font-bold text-slate-900">Izin & Sakit</p>
                <p class="text-xs text-slate-500">{{ $leavePending }} menunggu persetujuan</p>
            </div>
        </a>
    </section>
</div>
