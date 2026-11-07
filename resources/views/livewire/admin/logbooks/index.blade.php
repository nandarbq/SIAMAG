<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Verifikasi</p>
            <h1 class="page-title mt-1">Review Logbook</h1>
            <p class="page-subtitle">Periksa aktivitas harian mahasiswa dan berikan persetujuan atau catatan revisi.</p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statuses as $statusOption)
            @php($count = (int) ($counts[$statusOption->value] ?? 0))
            <button
                type="button"
                wire:click="$set('status', '{{ $statusOption->value }}')"
                class="card card-body text-left transition hover:-translate-y-0.5 hover:shadow-card {{ $status === $statusOption->value ? 'ring-2 ring-brand-500/40' : '' }}"
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $statusOption->label() }}</p>
                    <x-icon name="book" class="h-4 w-4 text-slate-300" />
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $count }}</p>
            </button>
        @endforeach
    </div>

    <div class="card card-body">
        <div class="grid gap-3 lg:grid-cols-[1fr_160px_160px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, NIM, atau aktivitas..." class="input pl-10">
            </div>
            <input wire:model.live="dateFrom" type="date" class="input" title="Dari tanggal">
            <input wire:model.live="dateTo" type="date" class="input" title="Sampai tanggal">
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
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Kegiatan</th>
                        <th class="table-th">Durasi</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logbooks as $logbook)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-td">
                                <p class="font-semibold text-slate-800">{{ $logbook->user?->name ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400">NIM {{ $logbook->user?->nim ?: '-' }}</p>
                            </td>
                            <td class="table-td whitespace-nowrap">{{ $logbook->date?->translatedFormat('d M Y') }}</td>
                            <td class="table-td">
                                <p class="max-w-xs truncate font-medium text-slate-700">{{ $logbook->activity }}</p>
                                <p class="max-w-xs truncate text-xs text-slate-400">{{ $logbook->result }}</p>
                            </td>
                            <td class="table-td whitespace-nowrap">{{ $logbook->duration ?? '-' }}</td>
                            <td class="table-td"><x-logbook-status :status="$logbook->status" /></td>
                            <td class="table-td">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="openReview({{ $logbook->id }})" class="btn-ghost btn-sm">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Review
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <x-icon name="book" class="h-10 w-10 text-slate-300" />
                                <p class="font-semibold text-slate-600">Tidak ada logbook</p>
                                <p class="text-xs">Belum ada data pada filter yang dipilih.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logbooks->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $logbooks->links() }}
            </div>
        @endif
    </div>

    @if ($reviewing)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="max-h-[92vh] w-full max-w-2xl animate-fade-up overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <x-logbook-status :status="$reviewing->status" />
                            <span class="text-xs text-slate-400">{{ $reviewing->date?->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <h2 class="mt-2 text-lg font-bold text-slate-900">{{ $reviewing->user?->name }}</h2>
                        <p class="text-sm text-slate-500">NIM {{ $reviewing->user?->nim ?: '-' }}</p>
                    </div>
                    <button type="button" wire:click="closeReview" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Aktivitas</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $reviewing->activity }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Mulai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->start_time ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Selesai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->end_time ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Durasi</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $reviewing->duration ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Uraian Kegiatan</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $reviewing->description }}</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Hasil</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $reviewing->result }}</p>
                    </div>

                    @if ($reviewing->obstacle)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600">Kendala</p>
                            <p class="mt-1 text-sm leading-relaxed text-amber-800">{{ $reviewing->obstacle }}</p>
                        </div>
                    @endif

                    @if ($reviewing->status === \App\Enums\LogbookStatus::Submitted)
                        <div class="border-t border-slate-100 pt-5">
                            <label class="label">Catatan Review</label>
                            <textarea wire:model="reviewNote" rows="3" class="input" placeholder="Catatan untuk mahasiswa (wajib jika menolak)..."></textarea>
                            @error('reviewNote') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button type="button" wire:click="reject({{ $reviewing->id }})" class="btn-danger">
                                    <x-icon name="x" class="h-4 w-4" />
                                    Tolak & Minta Revisi
                                </button>
                                <button type="button" wire:click="approve({{ $reviewing->id }})" class="btn-primary">
                                    <x-icon name="check-circle" class="h-4 w-4" />
                                    Setujui Logbook
                                </button>
                            </div>
                        </div>
                    @else
                        @if ($reviewing->review_note)
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Catatan Review</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $reviewing->review_note }}</p>
                                <p class="mt-2 text-xs text-slate-400">Direview {{ $reviewing->reviewed_at?->translatedFormat('d M Y H:i') }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
