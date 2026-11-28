<div class="space-y-5">
    <div class="card card-body">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-[minmax(0,1fr)_150px_150px]">
            <div class="relative col-span-2 sm:col-span-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode, nama, atau kampus..." class="input w-full pl-10">
            </div>
            <select wire:model.live="status" class="input">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
                <option value="completed">Selesai</option>
            </select>
            <select wire:model.live="type" class="input">
                <option value="">Semua jenis</option>
                <option value="group">Kelompok</option>
                <option value="individual">Mandiri</option>
            </select>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($groups->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 px-6 py-16 text-center">
                <x-icon name="building" class="h-10 w-10 text-slate-300" />
                <p class="font-semibold text-slate-600">Tidak ada kelompok ditemukan</p>
                <p class="text-xs text-slate-500">Buat kelompok baru agar mahasiswa bisa dikelompokkan.</p>
            </div>
        @else
            <x-table-scroll-hint />
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60">
                        <tr>
                            <th class="table-th">Kelompok</th>
                            <th class="table-th">Kampus</th>
                            <th class="table-th">Periode</th>
                            <th class="table-th">Anggota</th>
                            <th class="table-th">Status</th>
                            <th class="table-th text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($groups as $group)
                            <tr wire:key="{{ $group->id }}" class="transition hover:bg-slate-50/60">
                                <td class="table-td">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $group->name }}</p>
                                        @if ($group->is_individual)
                                            <div class="mt-1">
                                                <span class="badge-slate badge-compact">Mandiri</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="table-td">
                                    <p class="font-medium text-slate-700">{{ $group->institution }}</p>
                                </td>
                                <td class="table-td">
                                    <p class="text-xs font-medium text-slate-700">{{ $group->periodLabel() }}</p>
                                </td>
                                <td class="table-td">
                                    <span class="badge-slate badge-compact">{{ $group->students_count }} mahasiswa</span>
                                </td>
                                <td class="table-td">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($group->isCompleted())
                                            <span class="badge-amber badge-compact">Selesai</span>
                                        @elseif ($group->is_active)
                                            <span class="badge-green badge-compact">Aktif</span>
                                        @else
                                            <span class="badge-slate badge-compact">Nonaktif</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="table-td">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" wire:click="$dispatch('show-group', { groupId: {{ $group->id }} })" class="btn-ghost btn-sm" title="Lihat detail">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="$dispatch('edit-group', { groupId: {{ $group->id }} })" class="btn-ghost btn-sm" title="Edit">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="$dispatch('app-confirm', {
                                                title: 'Hapus kelompok?',
                                                message: @js('Kelompok '.$group->code.' akan dihapus permanen. Pastikan tidak ada anggota yang masih terdaftar.'),
                                                confirmLabel: 'Hapus kelompok',
                                                workingLabel: 'Menghapus...',
                                                onConfirm: () => $wire.delete({{ $group->id }}),
                                            })"
                                            class="btn-ghost btn-sm text-rose-600"
                                            title="Hapus"
                                        >
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($groups->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $groups->links() }}
            </div>
        @endif
    </div>
</div>
