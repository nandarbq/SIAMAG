<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Rekapitulasi</p>
            <h1 class="page-title mt-1">Laporan Magang</h1>
            <p class="page-subtitle">Rekap absensi dan logbook mahasiswa per bulan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.excel', ['month' => $month, 'year' => $year, 'student_id' => $studentId]) }}" class="btn-secondary">
                <x-icon name="download" class="h-4 w-4" />
                Excel
            </a>
            <a href="{{ route('admin.reports.pdf', ['month' => $month, 'year' => $year, 'student_id' => $studentId]) }}" class="btn-secondary">
                <x-icon name="document" class="h-4 w-4" />
                PDF
            </a>
            <a href="{{ route('admin.reports.word', ['month' => $month, 'year' => $year, 'student_id' => $studentId]) }}" class="btn-secondary">
                <x-icon name="document" class="h-4 w-4" />
                Word
            </a>
        </div>
    </div>

    <div class="card card-body">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[160px_140px_1fr]">
            <select wire:model.live="month" class="input">
                @foreach ($months as $monthValue => $monthLabel)
                    <option value="{{ $monthValue }}">{{ $monthLabel }}</option>
                @endforeach
            </select>
            <select wire:model.live="year" class="input">
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                @endforeach
            </select>
            <select wire:model.live="studentId" class="input">
                <option value="">Semua mahasiswa</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->nim ?: '-' }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @php
            $cards = [
                ['label' => 'Mahasiswa', 'value' => $report['summary']['students'], 'tone' => 'brand', 'icon' => 'users'],
                ['label' => 'Hadir', 'value' => $report['summary']['hadir'], 'tone' => 'emerald', 'icon' => 'check-circle'],
                ['label' => 'Terlambat', 'value' => $report['summary']['terlambat'], 'tone' => 'amber', 'icon' => 'clock'],
                ['label' => 'Alpha', 'value' => $report['summary']['alpha'], 'tone' => 'rose', 'icon' => 'alert'],
                ['label' => 'Logbook Disetujui', 'value' => $report['summary']['logbook_approved'], 'tone' => 'violet', 'icon' => 'book'],
            ];
            $toneClasses = [
                'brand' => 'bg-brand-50 text-brand-600',
                'emerald' => 'bg-emerald-50 text-emerald-600',
                'amber' => 'bg-amber-50 text-amber-600',
                'rose' => 'bg-rose-50 text-rose-600',
                'violet' => 'bg-violet-50 text-violet-600',
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="card card-body">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $toneClasses[$card['tone']] }}">
                        <x-icon :name="$card['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.35fr]">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Komposisi Kehadiran</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $report['period_label'] }}</p>
                </div>
            </div>
            <div class="card-body">
                <div class="h-64" wire:ignore x-data="{
                    chart: null,
                    init() {
                        const data = JSON.parse(this.$el.dataset.chart);
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha'],
                                datasets: [{
                                    data: [data.hadir, data.terlambat, data.izin, data.sakit, data.alpha],
                                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#f43f5e'],
                                    borderWidth: 0,
                                    hoverOffset: 6,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '68%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14, font: { size: 11, family: 'Instrument Sans' } } },
                                },
                            },
                        });
                    },
                }" data-chart='@json($report['summary'])'>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Rekap per Mahasiswa</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $report['office_name'] }} · {{ $report['period_label'] }}</p>
                </div>
                <span class="badge-slate">{{ $report['rows']->count() }} mahasiswa</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60">
                        <tr>
                            <th class="table-th">Mahasiswa</th>
                            <th class="table-th text-center">Hadir</th>
                            <th class="table-th text-center">Terlambat</th>
                            <th class="table-th text-center">Izin/Sakit</th>
                            <th class="table-th text-center">Alpha</th>
                            <th class="table-th text-center">Logbook</th>
                            <th class="table-th">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['rows'] as $row)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="table-td">
                                    <p class="font-semibold text-slate-800">{{ $row['name'] }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $row['nim'] }} · {{ $row['institution'] }}</p>
                                </td>
                                <td class="table-td text-center font-semibold text-emerald-600">{{ $row['hadir'] }}</td>
                                <td class="table-td text-center font-semibold text-amber-600">{{ $row['terlambat'] }}</td>
                                <td class="table-td text-center">{{ $row['izin'] + $row['sakit'] }}</td>
                                <td class="table-td text-center font-semibold text-rose-600">{{ $row['alpha'] }}</td>
                                <td class="table-td text-center">
                                    <span class="text-xs font-semibold text-slate-700">{{ $row['logbook_approved'] }}/{{ $row['logbook_total'] }}</span>
                                </td>
                                <td class="table-td">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $row['percentage'] >= 80 ? 'bg-emerald-500' : ($row['percentage'] >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ min(100, $row['percentage']) }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700">{{ $row['percentage'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">Belum ada data untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
                Hari kerja dihitung berdasarkan periode magang, akhir pekan, dan hari libur nasional.
            </div>
        </div>
    </div>
</div>
