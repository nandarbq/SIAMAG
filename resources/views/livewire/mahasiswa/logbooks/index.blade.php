<div class="space-y-6">
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="page-eyebrow">Aktivitas Harian</p>
            <h1 class="page-title">Logbook Magang</h1>
            <p class="page-subtitle">Catat pekerjaan harian Anda dan kirim untuk direview oleh pembimbing.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tulis Logbook
        </button>
    </section>

    @if (! $profile)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Profil magang belum lengkap. Logbook tetap dapat disimpan sebagai draft, namun periode magang belum dapat divalidasi.</p>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Total Logbook', 'value' => $total, 'icon' => 'book', 'tone' => 'brand'],
                ['label' => 'Disetujui', 'value' => $counts[App\Enums\LogbookStatus::Approved->value] ?? 0, 'icon' => 'check-circle', 'tone' => 'emerald'],
                ['label' => 'Menunggu Review', 'value' => $counts[App\Enums\LogbookStatus::Submitted->value] ?? 0, 'icon' => 'clock', 'tone' => 'amber'],
                ['label' => 'Perlu Revisi', 'value' => $counts[App\Enums\LogbookStatus::Rejected->value] ?? 0, 'icon' => 'alert', 'tone' => 'rose'],
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

    <div class="card">
        <div class="card-header flex-col items-start gap-3 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-base font-bold text-slate-900">Daftar Logbook</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $total }} entri tercatat</p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari aktivitas..." class="input pl-9 sm:w-56">
                </div>
                <select wire:model.live="status" class="input sm:w-44">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
                @if ($search !== '' || $status !== '')
                    <button type="button" wire:click="resetFilters" class="btn-ghost btn-sm">Reset</button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Aktivitas</th>
                        <th class="table-th">Durasi</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logbooks as $logbook)
                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="table-td whitespace-nowrap">
                                <p class="font-semibold text-slate-800">{{ $logbook->date?->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">{{ $logbook->date?->translatedFormat('l') }}</p>
                            </td>
                            <td class="table-td">
                                <p class="font-semibold text-slate-800">{{ $logbook->activity }}</p>
                                @if ($logbook->description)
                                    <p class="mt-1 line-clamp-2 max-w-xl text-xs leading-relaxed text-slate-500">{{ $logbook->description }}</p>
                                @endif
                                @if ($logbook->status === App\Enums\LogbookStatus::Rejected && $logbook->review_note)
                                    <p class="mt-2 rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs text-rose-700">
                                        <span class="font-semibold">Catatan reviewer:</span> {{ $logbook->review_note }}
                                    </p>
                                @endif
                            </td>
                            <td class="table-td whitespace-nowrap text-xs text-slate-500">
                                @if ($logbook->start_time && $logbook->end_time)
                                    <p>{{ substr($logbook->start_time, 0, 5) }} — {{ substr($logbook->end_time, 0, 5) }}</p>
                                    <p class="mt-0.5 font-medium text-slate-600">{{ $logbook->duration }}</p>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="table-td"><x-logbook-status :status="$logbook->status" /></td>
                            <td class="table-td">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($logbook->isEditable())
                                        <button type="button" wire:click="openEdit({{ $logbook->id }})" class="btn-ghost btn-sm" title="Ubah logbook">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="submitExisting({{ $logbook->id }})" wire:confirm="Kirim logbook ini untuk review?" class="btn-secondary btn-sm" title="Kirim untuk review">
                                            <x-icon name="arrow-up-circle" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="delete({{ $logbook->id }})" wire:confirm="Hapus logbook ini?" class="btn-ghost btn-sm text-rose-600 hover:bg-rose-50" title="Hapus logbook">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @elseif ($logbook->status === App\Enums\LogbookStatus::Submitted)
                                        <span class="text-xs text-slate-400">Menunggu review</span>
                                    @else
                                        <span class="text-xs text-emerald-600">Selesai</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <x-icon name="book" class="h-6 w-6" />
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada logbook</p>
                                <p class="mt-1 text-xs text-slate-500">Mulai catat aktivitas magang Anda hari ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logbooks->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $logbooks->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $logbookId ? 'Ubah Logbook' : 'Tulis Logbook' }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Lengkapi aktivitas yang Anda kerjakan.</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="btn-ghost btn-sm" title="Tutup">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <form wire:submit="submit" class="space-y-5 p-6">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-1">
                                <label class="form-label">Tanggal</label>
                                <input type="date" wire:model="date" max="{{ now()->toDateString() }}" class="input">
                                @error('date') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" wire:model="startTime" class="input">
                                @error('startTime') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" wire:model="endTime" class="input">
                                @error('endTime') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Aktivitas Utama</label>
                            <input type="text" wire:model="activity" placeholder="Contoh: Analisis data curah hujan harian" class="input">
                            @error('activity') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Uraian Pekerjaan</label>
                            <textarea wire:model="description" rows="3" placeholder="Jelaskan langkah kerja, data yang digunakan, dan proses yang dilakukan..." class="input resize-none"></textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Hasil</label>
                                <textarea wire:model="result" rows="3" placeholder="Hasil atau output dari pekerjaan..." class="input resize-none"></textarea>
                                @error('result') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Kendala</label>
                                <textarea wire:model="obstacle" rows="3" placeholder="Kendala yang dihadapi (opsional)..." class="input resize-none"></textarea>
                                @error('obstacle') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <button type="button" wire:click="closeModal" class="btn-ghost">Batal</button>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft" class="btn-secondary">
                                    <span wire:loading.remove wire:target="saveDraft">Simpan Draft</span>
                                    <span wire:loading wire:target="saveDraft">Menyimpan...</span>
                                </button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="btn-primary">
                                    <x-icon name="arrow-up-circle" class="h-4 w-4" />
                                    <span wire:loading.remove wire:target="submit">Kirim untuk Review</span>
                                    <span wire:loading wire:target="submit">Mengirim...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
