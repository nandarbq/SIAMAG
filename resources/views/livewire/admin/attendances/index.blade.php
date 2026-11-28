<div
    class="space-y-6"
    x-data
    x-effect="document.documentElement.classList.toggle('overflow-hidden', $wire.editingId !== null || $wire.viewingId !== null)"
>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Monitoring</p>
            <h1 class="page-title mt-1">Data Absensi</h1>
            <p class="page-subtitle">Pantau kehadiran mahasiswa, lokasi, dan foto selfie absensi.</p>
        </div>
        <a href="{{ route('admin.reports') }}" class="btn-primary">
            <x-icon name="download" class="h-4 w-4" />
            Laporan & Ekspor
        </a>
    </div>

    <div class="card card-body">
        <div class="grid gap-3 lg:grid-cols-[1fr_170px_180px]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau NIM..." class="input pl-10">
            </div>
            <input wire:model.live="date" type="date" class="input">
            <select wire:model.live="status" class="input">
                <option value="">Semua status</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($statuses as $statusOption)
                <x-attendance-status-admin
                    :status="$statusOption"
                    :count="(int) ($summary[$statusOption->value] ?? 0)"
                    @class([
                        'px-3 py-1.5',
                        'outline-2 outline-solid outline-offset-2 outline-current' => $status === $statusOption->value,
                    ])
                />
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        <x-table-scroll-hint />
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <th class="table-th">Mahasiswa</th>
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Masuk / Pulang</th>
                        <th class="table-th">Lokasi</th>
                        <th class="table-th">Foto</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendances as $attendance)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-td">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-700">
                                        {{ strtoupper(substr($attendance->user?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $attendance->user?->name ?? '-' }}</p>
                                        <p class="text-[11px] text-slate-400">NIM {{ $attendance->user?->nim ?: '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">{{ $attendance->date?->translatedFormat('d M Y') }}</td>
                            <td class="table-td">
                                <p><span class="text-[11px] font-semibold uppercase text-slate-400">In</span> {{ $attendance->check_in_at?->format('H:i') ?? '-' }}</p>
                                <p><span class="text-[11px] font-semibold uppercase text-slate-400">Out</span> {{ $attendance->check_out_at?->format('H:i') ?? '-' }}</p>
                                @if ($attendance->early_leave_minutes > 0)
                                    <p class="text-[11px] font-medium text-amber-600">Pulang lebih awal {{ $attendance->early_leave_duration }}</p>
                                @endif
                            </td>
                            <td class="table-td">
                                @if ($attendance->check_in_distance !== null)
                                    <p class="text-xs font-medium text-slate-700">{{ $attendance->check_in_distance }} m</p>
                                    <p class="text-[11px] text-slate-400">Akurasi {{ $attendance->check_in_accuracy ?? '-' }} m</p>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex gap-1.5">
                                    @if ($attendance->check_in_photo)
                                        <a href="{{ route('attendance.photo', [$attendance, 'in']) }}" target="_blank" class="group relative h-10 w-10 overflow-hidden rounded-lg ring-1 ring-slate-200">
                                            <img src="{{ route('attendance.photo', [$attendance, 'in']) }}" alt="Selfie masuk" class="h-full w-full object-cover transition group-hover:scale-105">
                                        </a>
                                    @endif
                                    @if ($attendance->check_out_photo)
                                        <a href="{{ route('attendance.photo', [$attendance, 'out']) }}" target="_blank" class="group relative h-10 w-10 overflow-hidden rounded-lg ring-1 ring-slate-200">
                                            <img src="{{ route('attendance.photo', [$attendance, 'out']) }}" alt="Selfie pulang" class="h-full w-full object-cover transition group-hover:scale-105">
                                        </a>
                                    @endif
                                    @if (! $attendance->check_in_photo && ! $attendance->check_out_photo)
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="table-td"><x-attendance-status-admin :status="$attendance->status" /></td>
                            <td class="table-td">
                                <div class="flex justify-center gap-1">
                                    <button type="button" wire:click="openDetail({{ $attendance->id }})" class="btn-ghost btn-sm">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Detail
                                    </button>
                                    <button type="button" wire:click="openCorrect({{ $attendance->id }})" class="btn-ghost btn-sm">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                        Koreksi
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2 text-center">
                                    <x-icon name="clipboard-check" class="h-10 w-10 text-slate-300" />
                                    <p class="font-semibold text-slate-600">Tidak ada data absensi</p>
                                    <p class="text-xs text-slate-500">Ubah filter tanggal atau status untuk melihat data lain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendances->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

    @if ($viewingId && $viewing)
        @php
            $phases = [
                [
                    'label' => 'Masuk',
                    'time' => $viewing->check_in_at?->format('H:i'),
                    'photo' => $viewing->check_in_photo,
                    'photoUrl' => route('attendance.photo', [$viewing, 'in']),
                    'lat' => $viewing->check_in_lat,
                    'lng' => $viewing->check_in_lng,
                    'distance' => $viewing->check_in_distance,
                    'accuracy' => $viewing->check_in_accuracy,
                ],
                [
                    'label' => 'Pulang',
                    'time' => $viewing->check_out_at?->format('H:i'),
                    'photo' => $viewing->check_out_photo,
                    'photoUrl' => route('attendance.photo', [$viewing, 'out']),
                    'lat' => $viewing->check_out_lat,
                    'lng' => $viewing->check_out_lng,
                    'distance' => $viewing->check_out_distance,
                    'accuracy' => $viewing->check_out_accuracy,
                ],
            ];
        @endphp
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="max-h-[calc(100dvh-2rem)] w-full max-w-2xl animate-fade-up overflow-y-auto overscroll-contain rounded-3xl bg-white p-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Detail Absensi</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $viewing->user?->name ?? '-' }} · NIM {{ $viewing->user?->nim ?: '-' }} · {{ $viewing->date?->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeDetail" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                    <x-attendance-status-admin :status="$viewing->status" />
                    @if ($viewing->late_minutes > 0)
                        <span class="text-xs font-medium text-amber-700">Terlambat {{ $viewing->late_duration }}</span>
                    @endif
                    @if ($viewing->early_leave_minutes > 0)
                        <span class="text-xs font-medium text-amber-700">Pulang lebih awal {{ $viewing->early_leave_duration }}</span>
                    @endif
                    @if ($viewing->work_duration)
                        <span class="text-xs font-medium text-slate-600">Durasi kerja {{ $viewing->work_duration }}</span>
                    @endif
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($phases as $phase)
                        <section class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-bold text-slate-800">{{ $phase['label'] }}</h3>
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                    <x-icon name="clock" class="h-4 w-4 text-slate-400" />
                                    {{ $phase['time'] ?? '-' }}
                                </span>
                            </div>

                            <dl class="mt-4 space-y-3 text-xs">
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="flex items-center gap-1.5 text-slate-500">
                                        <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0" />
                                        Jarak dari kantor
                                    </dt>
                                    <dd class="text-right font-semibold text-slate-800">{{ $phase['distance'] !== null ? $phase['distance'].' m' : '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-slate-500">Akurasi GPS</dt>
                                    <dd class="text-right font-semibold text-slate-800">{{ $phase['accuracy'] !== null ? $phase['accuracy'].' m' : '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-slate-500">Koordinat</dt>
                                    <dd class="text-right font-semibold text-slate-800">
                                        @if ($phase['lat'] !== null && $phase['lng'] !== null)
                                            {{ number_format($phase['lat'], 7, '.', '') }}, {{ number_format($phase['lng'], 7, '.', '') }}
                                        @else
                                            -
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            @if ($phase['lat'] !== null && $phase['lng'] !== null)
                                <a
                                    href="https://www.google.com/maps?q={{ $phase['lat'] }},{{ $phase['lng'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-brand-700 hover:underline"
                                >
                                    <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                    Buka di Google Maps
                                </a>
                            @endif

                            @if ($phase['photo'])
                                <a href="{{ $phase['photoUrl'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 block overflow-hidden rounded-xl ring-1 ring-slate-200">
                                    <img src="{{ $phase['photoUrl'] }}" alt="Selfie {{ strtolower($phase['label']) }}" class="h-40 w-full object-cover">
                                </a>
                            @else
                                <div class="mt-3 flex h-24 items-center justify-center rounded-xl border border-dashed border-slate-200 text-xs text-slate-400">
                                    Tidak ada selfie
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Catatan</p>
                    <p class="mt-1.5 text-sm text-slate-700">{{ $viewing->note ?: 'Tidak ada catatan.' }}</p>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closeDetail" class="btn-secondary">Tutup</button>
                    <button type="button" wire:click="openCorrect({{ $viewing->id }})" class="btn-primary">
                        <x-icon name="pencil" class="h-4 w-4" />
                        Koreksi Absensi
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($editingId)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="max-h-[calc(100dvh-2rem)] w-full max-w-lg animate-fade-up overflow-y-auto overscroll-contain rounded-3xl bg-white p-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Koreksi Absensi</h2>
                        <p class="mt-1 text-sm text-slate-500">Perbarui status atau jam absensi mahasiswa.</p>
                    </div>
                    <button type="button" wire:click="closeCorrect" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="saveCorrect" class="mt-5 space-y-4">
                    <div>
                        <label class="label">Status</label>
                        <select wire:model="editStatus" class="input">
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        @error('editStatus') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Jam Masuk</label>
                            <input wire:model="editCheckIn" type="time" class="input">
                            @error('editCheckIn') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Jam Pulang</label>
                            <input wire:model="editCheckOut" type="time" class="input">
                            @error('editCheckOut') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Catatan Admin</label>
                        <textarea wire:model="editNote" rows="3" class="input" placeholder="Alasan koreksi atau catatan tambahan..."></textarea>
                        @error('editNote') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeCorrect" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">
                            <x-icon name="check-circle" class="h-4 w-4" />
                            Simpan Koreksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
