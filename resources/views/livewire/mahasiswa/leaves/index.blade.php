<div class="space-y-6">
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="page-eyebrow">Perizinan</p>
            <h1 class="page-title">Izin & Sakit</h1>
            <p class="page-subtitle">Catat ketidakhadiran dengan alasan yang jelas dan lampiran pendukung.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Ajukan Izin/Sakit
        </button>
    </section>

    @if (! $profile)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Profil magang belum lengkap. Hubungi admin agar pengajuan dapat divalidasi terhadap periode magang.</p>
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Total Pengajuan', 'value' => $total, 'icon' => 'calendar', 'tone' => 'brand'],
                ['label' => 'Tercatat', 'value' => $counts[App\Enums\LeaveStatus::Approved->value] ?? 0, 'icon' => 'check-circle', 'tone' => 'emerald'],
                ['label' => 'Sakit Berlangsung', 'value' => $ongoingSick, 'icon' => 'clock', 'tone' => 'amber'],
                ['label' => 'Ditolak', 'value' => $counts[App\Enums\LeaveStatus::Rejected->value] ?? 0, 'icon' => 'x', 'tone' => 'rose'],
            ];
            $tones = [
                'brand' => 'bg-brand-50 text-brand-600',
                'amber' => 'bg-amber-50 text-amber-600',
                'emerald' => 'bg-emerald-50 text-emerald-600',
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
                <h2 class="text-base font-bold text-slate-900">Riwayat Pengajuan</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $total }} pengajuan tercatat</p>
            </div>
            <div class="flex w-full items-center gap-2 sm:w-auto">
                <select wire:model.live="status" class="input sm:w-44">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
                @if ($status !== '')
                    <button type="button" wire:click="resetFilters" class="btn-ghost btn-sm">Reset</button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Jenis</th>
                        <th class="table-th">Alasan</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($leaves as $leave)
                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="table-td whitespace-nowrap">
                                <p class="font-semibold text-slate-800">{{ $leave->start_date?->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">
                                    @if ($leave->isOngoing())
                                        Berlangsung · {{ $leave->totalDays() }} hari
                                    @else
                                        s.d. {{ $leave->end_date?->translatedFormat('d M Y') }}
                                        · {{ $leave->totalDays() }} hari
                                    @endif
                                </p>
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $leave->type === App\Enums\LeaveType::Sakit ? 'badge-blue' : 'badge-amber' }}">
                                    {{ $leave->type->label() }}
                                </span>
                            </td>
                            <td class="table-td">
                                <p class="max-w-md text-sm leading-relaxed text-slate-600">{{ $leave->reason }}</p>
                                @if ($leave->status === App\Enums\LeaveStatus::Rejected && $leave->review_note)
                                    <p class="mt-2 rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs text-rose-700">
                                        <span class="font-semibold">Catatan admin:</span> {{ $leave->review_note }}
                                    </p>
                                @endif
                            </td>
                            <td class="table-td"><x-leave-status :status="$leave->status" /></td>
                            <td class="table-td">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($leave->attachment_path)
                                        <a href="{{ route('leave.attachment', $leave) }}" target="_blank" class="btn-ghost btn-sm" title="Lihat lampiran">
                                            <x-icon name="document" class="h-4 w-4" />
                                        </a>
                                    @endif
                                    @if ($leave->type === App\Enums\LeaveType::Sakit && $leave->isOngoing() && $leave->status === App\Enums\LeaveStatus::Approved)
                                        <button type="button" wire:click="openRecovery({{ $leave->id }})" class="btn-ghost btn-sm text-emerald-600 hover:bg-emerald-50" title="Saya sudah sembuh">
                                            <x-icon name="check-circle" class="h-4 w-4" />
                                        </button>
                                    @endif

                                    @php
                                        $canCancel = $leave->status === App\Enums\LeaveStatus::Pending
                                            || ($leave->status === App\Enums\LeaveStatus::Approved
                                                && (($leave->type === App\Enums\LeaveType::Izin && $leave->start_date->greaterThanOrEqualTo(today()))
                                                    || ($leave->type === App\Enums\LeaveType::Sakit && $leave->isOngoing())));
                                    @endphp

                                    @if ($canCancel)
                                        <button type="button" wire:click="delete({{ $leave->id }})" wire:confirm="Batalkan pengajuan ini? Absensi terkait juga akan dihapus." class="btn-ghost btn-sm text-rose-600 hover:bg-rose-50" title="Batalkan pengajuan">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <x-icon name="calendar" class="h-6 w-6" />
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada pengajuan</p>
                                <p class="mt-1 text-xs text-slate-500">Pengajuan izin atau sakit akan tampil di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leaves->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $leaves->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Ajukan Izin atau Sakit</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Pengajuan langsung dicatat tanpa persetujuan admin.</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="btn-ghost btn-sm" title="Tutup">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <form wire:submit="submit" class="space-y-5 p-6">
                        <div>
                            <label class="form-label">Jenis Pengajuan</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($types as $typeOption)
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="type" value="{{ $typeOption->value }}" class="peer sr-only">
                                        <span class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700">
                                            <x-icon :name="$typeOption === App\Enums\LeaveType::Sakit ? 'alert' : 'calendar'" class="h-4 w-4" />
                                            {{ $typeOption->label() }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">
                                {{ $type === App\Enums\LeaveType::Sakit->value ? 'Tanggal Mulai Sakit' : 'Tanggal Izin' }}
                            </label>
                            <input type="date" wire:model="startDate" class="input">
                            @error('startDate') <p class="form-error">{{ $message }}</p> @enderror

                            @if ($type === App\Enums\LeaveType::Sakit->value)
                                <p class="mt-2 text-xs leading-relaxed text-slate-500">
                                    Tanggal selesai tidak diperlukan. Setelah sembuh, tandai “Saya sudah sembuh” dan pilih tanggal kembali masuk.
                                </p>
                            @endif
                        </div>

                        <div>
                            <label class="form-label">Alasan</label>
                            <textarea wire:model="reason" rows="4" placeholder="Jelaskan alasan ketidakhadiran secara singkat dan jelas..." class="input resize-none"></textarea>
                            @error('reason') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Lampiran Pendukung <span class="font-normal text-slate-400">(opsional)</span></label>
                            <label for="leave-attachment" class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50/40">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-sm ring-1 ring-slate-200">
                                    <x-icon name="document" class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1 text-xs">
                                    @if ($attachment)
                                        <span class="block truncate font-semibold text-slate-700">{{ $attachment->getClientOriginalName() }}</span>
                                        <span class="text-slate-400">Klik untuk mengganti lampiran</span>
                                    @else
                                        <span class="block font-semibold text-slate-700">Unggah surat izin atau keterangan dokter</span>
                                        <span class="text-slate-400">JPG, PNG, atau PDF maksimal 4 MB</span>
                                    @endif
                                </span>
                                <span wire:loading wire:target="attachment" class="h-4 w-4 animate-spin rounded-full border-2 border-brand-200 border-t-brand-600"></span>
                                <input id="leave-attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" wire:model="attachment" class="sr-only">
                            </label>
                            @error('attachment') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                            <button type="button" wire:click="closeModal" class="btn-ghost">Batal</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="btn-primary">
                                <x-icon name="arrow-up-circle" class="h-4 w-4" />
                                <span wire:loading.remove wire:target="submit">Catat Pengajuan</span>
                                <span wire:loading wire:target="submit">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showRecoveryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="closeRecoveryModal"></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Saya Sudah Sembuh</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Masukkan tanggal kamu kembali masuk. Absensi sakit dicatat sampai H-1.</p>
                        </div>
                        <button type="button" wire:click="closeRecoveryModal" class="btn-ghost btn-sm" title="Tutup">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <form wire:submit="markRecovered" class="space-y-5 p-6">
                        <div>
                            <label class="form-label">Tanggal Kembali Masuk</label>
                            <input type="date" wire:model="recoveryDate" class="input">
                            <p class="mt-1 text-xs text-slate-500">Jika sembuh dan masuk hari ini, pilih hari ini.</p>
                            @error('recoveryDate') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                            <button type="button" wire:click="closeRecoveryModal" class="btn-ghost">Batal</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="markRecovered" class="btn-primary">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                <span wire:loading.remove wire:target="markRecovered">Saya sudah sembuh</span>
                                <span wire:loading wire:target="markRecovered">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
