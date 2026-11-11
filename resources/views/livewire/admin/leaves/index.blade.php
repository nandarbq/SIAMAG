<div class="space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Persetujuan</p>
        <h1 class="page-title mt-1">Izin & Sakit</h1>
        <p class="page-subtitle">Tinjau pengajuan ketidakhadiran mahasiswa dan perbarui absensi otomatis.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ($statuses as $statusOption)
            @php($count = (int) ($counts[$statusOption->value] ?? 0))
            <button
                type="button"
                wire:click="$set('status', '{{ $statusOption->value }}')"
                class="card card-body text-left transition hover:-translate-y-0.5 hover:shadow-card {{ $status === $statusOption->value ? 'ring-2 ring-brand-500/40' : '' }}"
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $statusOption->label() }}</p>
                    <x-leave-status :status="$statusOption" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $count }}</p>
            </button>
        @endforeach
    </div>

    <div class="card card-body">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, NIM, atau alasan..." class="input pl-10">
            </div>
            <select wire:model.live="type" class="input">
                <option value="">Semua jenis</option>
                @foreach ($types as $typeOption)
                    <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="resetFilters" class="btn-secondary">
                <x-icon name="filter" class="h-4 w-4" />
                Reset
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <th class="table-th">Mahasiswa</th>
                        <th class="table-th">Jenis</th>
                        <th class="table-th">Periode</th>
                        <th class="table-th">Alasan</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($leaves as $leave)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-td">
                                <p class="font-semibold text-slate-800">{{ $leave->user?->name ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400">NIM {{ $leave->user?->nim ?: '-' }}</p>
                            </td>
                            <td class="table-td">
                                <span class="{{ $leave->type === \App\Enums\LeaveType::Izin ? 'badge-blue' : 'badge-amber' }}">{{ $leave->type->label() }}</span>
                            </td>
                            <td class="table-td">
                                <p class="whitespace-nowrap text-sm font-medium text-slate-700">{{ $leave->start_date?->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">{{ $leave->totalDays() }} hari</p>
                            </td>
                            <td class="table-td">
                                <p class="max-w-xs truncate text-sm text-slate-600">{{ $leave->reason }}</p>
                            </td>
                            <td class="table-td"><x-leave-status :status="$leave->status" /></td>
                            <td class="table-td">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="openReview({{ $leave->id }})" class="btn-ghost btn-sm">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <x-icon name="calendar" class="h-10 w-10 text-slate-300" />
                                <p class="font-semibold text-slate-600">Tidak ada pengajuan</p>
                                <p class="text-xs">Belum ada data pada filter yang dipilih.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leaves->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $leaves->links() }}
            </div>
        @endif
    </div>

    @if ($reviewing)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="w-full max-w-lg animate-fade-up rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Detail Pengajuan</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $reviewing->user?->name }}</p>
                    </div>
                    <button type="button" wire:click="closeReview" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Jenis</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->type->label() }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Durasi</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->totalDays() }} hari</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Mulai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->start_date?->translatedFormat('d M Y') }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Selesai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->end_date?->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Alasan</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $reviewing->reason }}</p>
                    </div>

                    @if ($reviewing->attachment_path)
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Lampiran</p>
                            <a href="{{ route('leave.attachment', $reviewing) }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
                                <x-icon name="document" class="h-4 w-4" />
                                Lihat lampiran
                            </a>
                        </div>
                    @endif

                    @if ($reviewing->status === \App\Enums\LeaveStatus::Pending)
                        <div class="border-t border-slate-100 pt-4">
                            <label class="label">Catatan Review</label>
                            <textarea wire:model="reviewNote" rows="3" class="input" placeholder="Catatan persetujuan atau alasan penolakan..."></textarea>
                            @error('reviewNote') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button type="button" wire:click="reject({{ $reviewing->id }})" class="btn-danger">
                                    <x-icon name="x" class="h-4 w-4" />
                                    Tolak
                                </button>
                                <button type="button" wire:click="approve({{ $reviewing->id }})" class="btn-primary">
                                    <x-icon name="check-circle" class="h-4 w-4" />
                                    Setujui
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Status</span>
                                <x-leave-status :status="$reviewing->status" />
                            </div>
                            @if ($reviewing->review_note)
                                <p class="mt-2 text-sm text-slate-700">{{ $reviewing->review_note }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-400">Diproses {{ $reviewing->reviewed_at?->translatedFormat('d M Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
