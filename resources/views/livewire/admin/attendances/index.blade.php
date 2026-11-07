<div class="space-y-6">
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
        <div class="grid gap-3 lg:grid-cols-[170px_180px_1fr_auto]">
            <input wire:model.live="date" type="date" class="input">
            <select wire:model.live="status" class="input">
                <option value="">Semua status</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau NIM..." class="input pl-10">
            </div>
            <button type="button" wire:click="resetFilters" class="btn-secondary">
                <x-icon name="filter" class="h-4 w-4" />
                Reset
            </button>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($statuses as $statusOption)
                @php($count = (int) ($summary[$statusOption->value] ?? 0))
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">
                    {{ $statusOption->label() }}
                    <span class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-700">{{ $count }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
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
                        <th class="table-th text-right">Aksi</th>
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
                            <td class="table-td"><x-attendance-status :status="$attendance->status" /></td>
                            <td class="table-td">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="openCorrect({{ $attendance->id }})" class="btn-ghost btn-sm">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                        Koreksi
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <x-icon name="clipboard-check" class="h-10 w-10 text-slate-300" />
                                <p class="font-semibold text-slate-600">Tidak ada data absensi</p>
                                <p class="text-xs">Ubah filter tanggal atau status untuk melihat data lain.</p>
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

    @if ($editingId)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="w-full max-w-lg animate-fade-up rounded-3xl bg-white p-6 shadow-2xl">
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
