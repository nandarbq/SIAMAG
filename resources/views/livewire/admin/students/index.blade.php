<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Manajemen Magang</p>
            <h1 class="page-title mt-1">Data Mahasiswa</h1>
            <p class="page-subtitle">Kelola akun dan profil mahasiswa magang BMKG Banyuwangi.</p>
        </div>
        <a href="{{ route('admin.students.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah Mahasiswa
        </a>
    </div>

    <div class="card card-body">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, email, atau NIM..." class="input pl-10">
            </div>
            <select wire:model.live="status" class="input">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
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
                        <th class="table-th">Institusi</th>
                        <th class="table-th">Periode Magang</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">
                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $student->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $student->email }}</p>
                                        <p class="text-[11px] text-slate-400">NIM {{ $student->nim ?: '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                <p class="font-medium text-slate-700">{{ $student->internProfile?->institution ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ $student->internProfile?->major ?? '-' }}</p>
                            </td>
                            <td class="table-td">
                                @if ($student->internProfile)
                                    <p class="text-xs font-medium text-slate-700">
                                        {{ $student->internProfile->start_date?->translatedFormat('d M Y') }}
                                        <span class="text-slate-400">s.d.</span>
                                        {{ $student->internProfile->end_date?->translatedFormat('d M Y') }}
                                    </p>
                                    <div class="mt-2 h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $student->internProfile->progressPercent() }}%"></div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Belum diisi</span>
                                @endif
                            </td>
                            <td class="table-td">
                                @if ($student->is_active)
                                    <span class="badge-green">Aktif</span>
                                @else
                                    <span class="badge-slate">Nonaktif</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.students.show', $student) }}" class="btn-ghost btn-sm" title="Lihat detail">
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </a>
                                    <a href="{{ route('admin.students.edit', $student) }}" class="btn-ghost btn-sm" title="Edit">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $student->id }})"
                                        class="btn-ghost btn-sm"
                                        title="{{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                    >
                                        <x-icon :name="$student->is_active ? 'lock' : 'check-circle'" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                <x-icon name="users" class="h-10 w-10 text-slate-300" />
                                <p class="font-semibold text-slate-600">Tidak ada mahasiswa ditemukan</p>
                                <p class="text-xs">Coba ubah kata kunci atau tambah mahasiswa baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
