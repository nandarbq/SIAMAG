<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Manajemen Magang</p>
            <h1 class="page-title mt-1">Data Mahasiswa</h1>
            <p class="page-subtitle">Kelola akun mahasiswa dan kelompok magang BMKG Banyuwangi.</p>
        </div>
        @if ($tab === 'groups')
            <button type="button" wire:click="$dispatch('create-group')" class="btn-primary w-full sm:w-auto">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Kelompok
            </button>
        @else
            <a href="{{ route('admin.students.create') }}" class="btn-primary w-full sm:w-auto">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Mahasiswa
            </a>
        @endif
    </div>

    <div class="flex items-center gap-1 overflow-x-auto border-b border-slate-200">
        <button
            type="button"
            wire:click="switchTab('students')"
            class="relative shrink-0 whitespace-nowrap px-4 py-2.5 text-sm font-semibold transition {{ $tab === 'students' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700' }}"
        >
            Mahasiswa
            @if ($tab === 'students')
                <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-brand-600"></span>
            @endif
        </button>
        <button
            type="button"
            wire:click="switchTab('groups')"
            class="relative shrink-0 whitespace-nowrap px-4 py-2.5 text-sm font-semibold transition {{ $tab === 'groups' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700' }}"
        >
            Kelompok
            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">{{ $groups->count() }}</span>
            @if ($tab === 'groups')
                <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-brand-600"></span>
            @endif
        </button>
    </div>

    @if ($tab === 'groups' && $groupView === 'list')
        <p class="text-xs text-slate-500">Data kampus, pembimbing, dan periode diisi sekali di sini, lalu otomatis dipakai oleh anggota.</p>
    @endif

    @if ($groupNotice)
        <div class="rounded-xl border px-4 py-3 text-sm font-medium {{ $groupNoticeType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
            {{ $groupNotice }}
        </div>
    @endif

    @if ($tab === 'students')
        <div class="card card-body">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <x-icon name="search" class="h-4 w-4" />
                    </span>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, email, atau NIM..." class="input w-full pl-10">
                </div>
                <select wire:model.live="status" class="input w-full">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="card overflow-hidden">
            @if ($students->isEmpty())
                <div class="flex flex-col items-center justify-center gap-2 px-6 py-16 text-center">
                    <x-icon name="users" class="h-10 w-10 text-slate-300" />
                    <p class="font-semibold text-slate-600">Tidak ada mahasiswa ditemukan</p>
                    <p class="text-xs text-slate-500">Coba ubah kata kunci atau tambah mahasiswa baru.</p>
                </div>
            @else
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
                            @foreach ($students as $student)
                                <tr wire:key="{{ $student->id }}" class="transition hover:bg-slate-50/60">
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
                                        @if ($student->internProfile?->group)
                                            <a href="{{ route('admin.students', ['tab' => 'groups', 'group' => $student->internProfile->group->id]) }}" class="mt-1 inline-flex text-[11px] font-semibold text-brand-600 hover:text-brand-700">
                                                {{ $student->internProfile->group->code }}
                                            </a>
                                        @endif
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($students->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    @else
        @if ($groupView === 'form')
            @if ($group)
                <livewire:admin.groups.form :group="$group" :embedded="true" :wire:key="'group-form-'.$groupId" />
            @else
                <livewire:admin.groups.form :embedded="true" wire:key="group-form-new" />
            @endif
        @elseif ($groupView === 'show' && $group)
            <livewire:admin.groups.show :group="$group" :embedded="true" :wire:key="'group-show-'.$groupId" />
        @else
            <livewire:admin.groups.index />
        @endif
    @endif
</div>
