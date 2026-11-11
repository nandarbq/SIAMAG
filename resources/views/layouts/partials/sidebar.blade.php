@php
    $isAdmin = auth()->user()->role === \App\Enums\UserRole::Admin;

    $adminNav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Data Mahasiswa', 'route' => 'admin.students', 'icon' => 'users'],
        ['label' => 'Absensi', 'route' => 'admin.attendances', 'icon' => 'clipboard-check'],
        ['label' => 'Logbook', 'route' => 'admin.logbooks', 'icon' => 'book'],
        ['label' => 'Izin & Sakit', 'route' => 'admin.leaves', 'icon' => 'calendar'],
        ['label' => 'Laporan', 'route' => 'admin.reports', 'icon' => 'chart'],
    ];

    $adminSettings = [
        ['label' => 'Hari Libur', 'route' => 'admin.holidays', 'icon' => 'calendar'],
        ['label' => 'Pengumuman', 'route' => 'admin.announcements', 'icon' => 'megaphone'],
        ['label' => 'Pengaturan', 'route' => 'admin.settings', 'icon' => 'settings'],
    ];

    $studentNav = [
        ['label' => 'Dashboard', 'route' => 'mahasiswa.dashboard', 'icon' => 'home'],
        ['label' => 'Absensi', 'route' => 'mahasiswa.attendance', 'icon' => 'camera'],
        ['label' => 'Logbook', 'route' => 'mahasiswa.logbooks', 'icon' => 'book'],
        ['label' => 'Izin & Sakit', 'route' => 'mahasiswa.leaves', 'icon' => 'calendar'],
        ['label' => 'Profil Saya', 'route' => 'mahasiswa.profile', 'icon' => 'user'],
    ];
@endphp

<div class="sidebar-bg flex h-full flex-col text-white">
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white p-1.5">
            <img src="{{ asset('images/logo-bmkg.png') }}" alt="Logo BMKG" class="h-full w-auto">
        </div>
        <div>
            <p class="text-base font-bold tracking-tight">SIAMAG</p>
            <p class="text-[10px] font-medium uppercase tracking-[0.18em] text-slate-300">BMKG Banyuwangi</p>
        </div>
    </div>

    <div class="scrollbar-thin flex-1 overflow-y-auto px-3 py-5">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">
            {{ $isAdmin ? 'Manajemen' : 'Ruang Mahasiswa' }}
        </p>

        <nav class="space-y-1">
            @foreach ($isAdmin ? $adminNav : $studentNav as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    @class([
                        'nav-link',
                        'nav-link-active' => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                    ])
                >
                    <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($isAdmin)
            <p class="mb-2 mt-7 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Konfigurasi</p>
            <nav class="space-y-1">
                @foreach ($adminSettings as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'nav-link',
                            'nav-link-active' => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                        ])
                    >
                        <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="mt-7 rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="flex items-center gap-2 text-xs font-semibold text-accent-300">
                <x-icon name="shield" class="h-4 w-4" />
                {{ $isAdmin ? 'Akses Administrator' : 'Magang Aktif' }}
            </div>
            <p class="mt-2 text-xs leading-relaxed text-slate-300">
                {{ $isAdmin ? 'Kelola mahasiswa, absensi, logbook, dan laporan magang.' : 'Isi absensi dan logbook harian agar progres magang tercatat rapi.' }}
            </p>
        </div>
    </div>

    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent-400 to-accent-600 text-sm font-bold text-slate-900">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate text-[11px] text-slate-400">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white" title="Keluar">
                    <x-icon name="logout" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</div>
