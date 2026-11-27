<div class="card">
    <div class="card-header">
        <div class="flex items-center gap-3">
            <span class="stat-icon bg-sky-50 text-sky-600">
                <x-icon name="shield" class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Akun Administrator</h2>
                <p class="text-xs text-slate-500">Kelola akun yang memiliki akses penuh ke panel admin.</p>
            </div>
        </div>
        <a href="{{ route('admin.settings.admins.create') }}" class="btn-primary btn-sm">
            <x-icon name="plus" class="h-3.5 w-3.5" />
            Tambah Admin
        </a>
    </div>

    @if ($notice)
        <div class="mx-5 mt-4 rounded-xl border px-4 py-3 text-sm {{ $noticeType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
            {{ $notice }}
        </div>
    @endif

    <x-table-scroll-hint />
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/60">
                <tr>
                    <th class="table-th">Administrator</th>
                    <th class="table-th">Status</th>
                    <th class="table-th">Dibuat</th>
                    <th class="table-th text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($admins as $admin)
                    <tr wire:key="admin-{{ $admin->id }}" class="transition hover:bg-slate-50/60">
                        <td class="table-td">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$admin" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">
                                        {{ $admin->name }}
                                        @if ($admin->id === auth()->id())
                                            <span class="badge-slate badge-compact">Anda</span>
                                        @endif
                                    </p>
                                    <p class="truncate text-xs text-slate-500">{{ $admin->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="table-td">
                            @if ($admin->is_active)
                                <span class="badge-green">Aktif</span>
                            @else
                                <span class="badge-slate">Nonaktif</span>
                            @endif
                            @error('admin_'.$admin->id) <p class="form-error">{{ $message }}</p> @enderror
                        </td>
                        <td class="table-td">{{ $admin->created_at?->translatedFormat('d M Y') }}</td>
                        <td class="table-td text-center">
                            <div class="flex flex-wrap justify-center gap-1">
                                <a href="{{ route('admin.settings.admins.edit', $admin) }}" class="btn-ghost btn-sm">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                    Edit
                                </a>
                                <button type="button" wire:click="toggleActive({{ $admin->id }})" class="btn-secondary btn-sm">
                                    {{ $admin->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada akun administrator.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
