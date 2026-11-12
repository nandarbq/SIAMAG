<div class="space-y-6">
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="page-eyebrow">Aktivitas Harian</p>
            <h1 class="page-title">Logbook Magang</h1>
            <p class="page-subtitle">Catat pekerjaan harian Anda agar laporan akhir magang lebih mudah disusun.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tulis Logbook
        </button>
    </section>

    @if (! $profile)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Profil magang belum lengkap. Logbook tetap dapat disimpan, namun periode magang belum dapat divalidasi.</p>
        </div>
    @endif

    @php
        $todayLabels = [
            'no_profile' => 'Profil belum ada',
            'not_started' => 'Belum mulai',
            'outside' => 'Di luar periode',
            'holiday' => 'Hari libur',
            'not_attended' => 'Belum absen',
            'excused' => 'Izin/Sakit',
            'filled' => 'Sudah diisi',
            'missing' => 'Belum diisi',
        ];
        $todayTone = match ($progress['today_status']) {
            'filled' => 'emerald',
            'missing' => 'rose',
            'not_attended' => 'amber',
            default => 'slate',
        };
        $stats = [
            [
                'label' => 'Progres Logbook',
                'value' => $progress['percentage'] === null ? '—' : $progress['percentage'].'%',
                'note' => $progress['expected'] > 0 ? $progress['filled'].' dari '.$progress['expected'].' hari kerja' : 'Belum ada hari kerja berjalan',
                'icon' => 'chart',
                'tone' => 'brand',
            ],
            [
                'label' => 'Logbook Terisi',
                'value' => $progress['total'],
                'note' => 'Total entri tersimpan',
                'icon' => 'book',
                'tone' => 'violet',
            ],
            [
                'label' => 'Belum Diisi',
                'value' => $progress['missing'],
                'note' => $progress['missing'] > 0 ? 'Perlu dilengkapi' : 'Semua sudah terisi',
                'icon' => 'alert',
                'tone' => $progress['missing'] > 0 ? 'rose' : 'emerald',
            ],
            [
                'label' => 'Hari Ini',
                'value' => $todayLabels[$progress['today_status']] ?? '-',
                'note' => now()->translatedFormat('l, d M Y'),
                'icon' => 'calendar',
                'tone' => $todayTone,
            ],
        ];
        $tones = [
            'brand' => 'bg-brand-50 text-brand-600',
            'violet' => 'bg-violet-50 text-violet-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'rose' => 'bg-rose-50 text-rose-600',
            'slate' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="card card-body animate-fade-up">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tones[$stat['tone']] }}">
                        <x-icon :name="$stat['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold leading-tight text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $stat['note'] }}</p>
            </div>
        @endforeach
    </section>

    @if ($progress['missing'] > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <div class="flex items-start gap-3">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">Ada {{ $progress['missing'] }} hari kerja yang belum diisi logbook.</p>
                    <p class="mt-0.5 text-xs">Lengkapi sekarang agar laporan akhir magang Anda tidak terhambat.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach (array_slice($progress['missing_dates'], 0, 8) as $missingDate)
                            <button type="button" wire:click="openCreateForDate('{{ $missingDate }}')" class="btn-secondary btn-sm">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                {{ \Carbon\Carbon::parse($missingDate)->translatedFormat('d M') }}
                            </button>
                        @endforeach
                        @if (count($progress['missing_dates']) > 8)
                            <span class="self-center text-xs font-medium text-amber-700">+{{ count($progress['missing_dates']) - 8 }} tanggal lainnya</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($progress['today_status'] === 'missing')
        <div class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 sm:flex-row sm:items-center">
            <div class="flex flex-1 items-start gap-3">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                <p>Logbook hari ini belum diisi. Luangkan waktu sejenak untuk mencatat aktivitas Anda.</p>
            </div>
            <button type="button" wire:click="openCreateForDate('{{ now()->toDateString() }}')" class="btn-secondary btn-sm self-start sm:self-auto">
                Isi hari ini
            </button>
        </div>
    @elseif ($progress['today_status'] === 'not_attended')
        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Absen masuk dulu, lalu isi logbook hari ini.</p>
        </div>
    @endif

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
                @if ($search !== '')
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
                            </td>
                            <td class="table-td whitespace-nowrap text-xs text-slate-500">
                                @if ($logbook->start_time && $logbook->end_time)
                                    <p>{{ substr($logbook->start_time, 0, 5) }} — {{ substr($logbook->end_time, 0, 5) }}</p>
                                    <p class="mt-0.5 font-medium text-slate-600">{{ $logbook->duration }}</p>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($logbook->isEditable())
                                        <button type="button" wire:click="openEdit({{ $logbook->id }})" class="btn-ghost btn-sm" title="Ubah logbook">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="delete({{ $logbook->id }})" wire:confirm="Hapus logbook ini?" class="btn-ghost btn-sm text-rose-600 hover:bg-rose-50" title="Hapus logbook">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">Terkunci</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center">
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

                    <form wire:submit="save" class="space-y-5 p-6">
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
                            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                <span wire:loading.remove wire:target="save">Simpan Logbook</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
