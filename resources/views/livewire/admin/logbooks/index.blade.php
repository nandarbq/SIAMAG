<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Monitoring</p>
            <h1 class="page-title mt-1">Monitoring Logbook</h1>
            <p class="page-subtitle">Pantau aktivitas harian mahasiswa. Logbook bersifat read-only dan hanya dapat diubah oleh mahasiswa.</p>
        </div>
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
                                @if ($logbook->attachment_path)
                                    <a href="{{ route('logbook.photo', $logbook) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700">
                                        <x-icon name="camera" class="h-3.5 w-3.5" />
                                        Foto kegiatan
                                    </a>
                                @endif
                            </td>
                            <td class="table-td whitespace-nowrap">{{ $logbook->duration ?? '-' }}</td>
                            <td class="table-td">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="openDetail({{ $logbook->id }})" class="btn-ghost btn-sm">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Lihat
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
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

    @if ($viewing)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="max-h-[92vh] w-full max-w-2xl animate-fade-up overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-6">
                    <div>
                        <span class="text-xs text-slate-400">{{ $viewing->date?->translatedFormat('l, d F Y') }}</span>
                        <h2 class="mt-2 text-lg font-bold text-slate-900">{{ $viewing->user?->name }}</h2>
                        <p class="text-sm text-slate-500">NIM {{ $viewing->user?->nim ?: '-' }}</p>
                    </div>
                    <button type="button" wire:click="closeDetail" class="btn-ghost px-2 py-2">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Aktivitas</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $viewing->activity }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Mulai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $viewing->start_time ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Selesai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $viewing->end_time ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Durasi</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $viewing->duration ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Uraian Kegiatan</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $viewing->description }}</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Hasil</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $viewing->result }}</p>
                    </div>

                    @if ($viewing->obstacle)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600">Kendala</p>
                            <p class="mt-1 text-sm leading-relaxed text-amber-800">{{ $viewing->obstacle }}</p>
                        </div>
                    @endif

                    @if ($viewing->attachment_path)
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Foto Kegiatan</p>
                            <a href="{{ route('logbook.photo', $viewing) }}" target="_blank" class="mt-2 block">
                                <img src="{{ route('logbook.photo', $viewing) }}" alt="Foto kegiatan" class="max-h-72 w-full rounded-xl bg-slate-50 object-contain ring-1 ring-slate-200">
                            </a>
                        </div>
                    @endif

                    <div class="border-t border-slate-100 pt-5">
                        <p class="text-xs text-slate-500">Logbook hanya dapat dilihat. Perubahan dilakukan oleh mahasiswa selama periode magang.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
