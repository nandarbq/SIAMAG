@php
    $activeAnnouncementCount = \App\Models\Announcement::query()
        ->where('is_active', true)
        ->where('published_at', '<=', now())
        ->count();
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIAMAG' }} — BMKG Banyuwangi</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-slate-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-72 transform transition-transform duration-300 ease-out lg:translate-x-0"
        >
            @include('layouts.partials.sidebar')
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="btn-ghost -ml-2 px-2.5 py-2 lg:hidden"
                            @click="sidebarOpen = true"
                            aria-label="Buka menu"
                        >
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>

                        <div class="hidden sm:block">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-brand-600">
                                {{ auth()->user()->role === \App\Enums\UserRole::Admin ? 'Panel Administrator' : 'Portal Mahasiswa' }}
                            </p>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ now()->translatedFormat('l, d F Y') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-4">
                        @if (auth()->user()->role === \App\Enums\UserRole::Mahasiswa)
                            <a href="{{ route('mahasiswa.dashboard') }}" class="btn-ghost relative px-2.5 py-2" aria-label="Pengumuman">
                                <x-icon name="bell" class="h-5 w-5" />
                                @if (($activeAnnouncementCount ?? 0) > 0)
                                    <span class="absolute right-1 top-1 flex h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                                @endif
                            </a>
                        @endif

                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-left shadow-sm transition hover:border-slate-300"
                            >
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-brand-800 text-xs font-bold text-white">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden min-w-0 sm:block">
                                    <span class="block max-w-[140px] truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                                    <span class="block text-[11px] text-slate-500">{{ auth()->user()->role->label() }}</span>
                                </span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top.right
                                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10"
                            >
                                <div class="border-b border-slate-100 px-3 py-2.5">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ auth()->user()->role === \App\Enums\UserRole::Admin ? route('admin.settings') : route('mahasiswa.profile') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                    <x-icon name="user" class="h-4 w-4" />
                                    {{ auth()->user()->role === \App\Enums\UserRole::Admin ? 'Pengaturan' : 'Profil Saya' }}
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                        <x-icon name="logout" class="h-4 w-4" />
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('success'))
                    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                        <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
