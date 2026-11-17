<div class="space-y-4">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
            @if ($embedded)
                <button type="button" wire:click="$dispatch('back-to-groups')" class="btn-secondary px-2.5 py-2">
                    <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
                </button>
            @else
                <a href="{{ route('admin.groups') }}" class="btn-secondary px-2.5 py-2">
                    <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
                </a>
            @endif
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-brand-600">{{ $group->code }}</p>
                <h2 class="text-lg font-bold text-slate-900">{{ $group->name }}</h2>
                <p class="text-xs text-slate-500">{{ $group->institution }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($embedded)
                <button type="button" wire:click="$dispatch('edit-group', { groupId: {{ $group->id }} })" class="btn-secondary">
                    <x-icon name="pencil" class="h-4 w-4" />
                    Edit
                </button>
            @else
                <a href="{{ route('admin.students', ['tab' => 'groups', 'group' => $group->id, 'edit' => 1]) }}" class="btn-secondary">
                    <x-icon name="pencil" class="h-4 w-4" />
                    Edit
                </a>
            @endif
            <a href="{{ route('admin.students.create', ['group' => $group->id]) }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Mahasiswa
            </a>
        </div>
    </div>

    <div class="card card-body">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Status</p>
                <p class="mt-1">
                    @if ($group->is_active)
                        <span class="badge-green">Aktif</span>
                    @else
                        <span class="badge-slate">Nonaktif</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Periode</p>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $group->periodLabel() }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Pembimbing</p>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $group->field_supervisor ?: '-' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">No. HP pembimbing</p>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $group->supervisor_phone ?: '-' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Divisi</p>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $group->division ?: '-' }}</p>
            </div>
        </div>
    </div>

    <section class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Anggota ({{ $students->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/60">
                    <tr>
                        <th class="table-th">Mahasiswa</th>
                        <th class="table-th">NIM</th>
                        <th class="table-th">Jurusan</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr wire:key="{{ $student->id }}">
                            <td class="table-td">
                                <p class="font-semibold text-slate-800">{{ $student->name }}</p>
                                <p class="text-xs text-slate-400">{{ $student->email }}</p>
                            </td>
                            <td class="table-td text-sm text-slate-600">{{ $student->nim ?: '-' }}</td>
                            <td class="table-td text-sm text-slate-600">{{ $student->internProfile?->major ?? '-' }}</td>
                            <td class="table-td">
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.students.show', $student) }}" class="btn-ghost btn-sm" title="Lihat detail">
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2 text-center">
                                    <x-icon name="users" class="h-10 w-10 text-slate-300" />
                                    <p class="font-semibold text-slate-600">Belum ada anggota</p>
                                    <p class="text-xs text-slate-500">Tambahkan mahasiswa pertama ke kelompok ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex items-center justify-between gap-4 rounded-xl border border-rose-200 bg-rose-50/40 px-4 py-3">
        <p class="text-xs text-rose-700">Kelompok beranggotakan mahasiswa tidak bisa dihapus.</p>
        <button
            type="button"
            wire:click="delete"
            wire:confirm="Yakin ingin menghapus kelompok {{ $group->code }}?"
            class="btn-danger"
        >
            <x-icon name="trash" class="h-4 w-4" />
            Hapus
        </button>
    </div>
</div>
