@php
    $user = auth()->user();
    $isAdmin = $user->role === \App\Enums\UserRole::Admin;
    $accountRoute = $isAdmin ? route('admin.settings') : route('mahasiswa.profile');
    $accountLabel = $isAdmin ? 'Pengaturan' : 'Profil Saya';
    $accountDescription = $isAdmin ? 'Kelola preferensi sistem' : 'Perbarui data akun Anda';
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
    <div
        x-data="{ sidebarOpen: false }"
        x-effect="document.body.classList.toggle('overflow-hidden', sidebarOpen)"
        @keydown.escape.window="sidebarOpen = false"
        class="min-h-screen"
    >
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            id="app-sidebar"
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-[min(18rem,82vw)] transform transition-transform duration-300 ease-out lg:translate-x-0"
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
                            aria-controls="app-sidebar"
                            :aria-expanded="sidebarOpen"
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

                    <div class="flex items-center gap-3">
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                :aria-expanded="open"
                                aria-haspopup="menu"
                                class="group flex items-center gap-2.5 rounded-full p-1 text-left transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 sm:pr-3"
                            >
                                <x-user-avatar :user="$user" size="sm" />
                                <span class="hidden min-w-0 sm:block">
                                    <span class="block max-w-[150px] truncate text-sm font-semibold leading-tight text-slate-800">{{ $user->name }}</span>
                                    <span class="block text-[11px] leading-tight text-slate-500">{{ $user->role->label() }}</span>
                                </span>
                                <x-icon
                                    name="chevron-down"
                                    class="hidden h-4 w-4 text-slate-400 transition-transform duration-200 sm:block"
                                    x-bind:class="open ? 'rotate-180' : ''"
                                />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top.right
                                class="absolute right-0 mt-3 w-72 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 p-2 shadow-2xl shadow-slate-900/10 backdrop-blur-xl"
                            >
                                <div class="flex items-center gap-3 rounded-xl bg-slate-50/80 p-3">
                                    <x-user-avatar :user="$user" size="md" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                        <span @class([
                                            'mt-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                            'bg-brand-50 text-brand-700' => $isAdmin,
                                            'bg-emerald-50 text-emerald-700' => ! $isAdmin,
                                        ])>
                                            {{ $user->role->label() }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <a
                                        href="{{ $accountRoute }}"
                                        wire:navigate
                                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                    >
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition group-hover:bg-white group-hover:text-brand-600">
                                            <x-icon :name="$isAdmin ? 'settings' : 'user'" class="h-4 w-4" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block">{{ $accountLabel }}</span>
                                            <span class="block text-[11px] font-normal text-slate-400">{{ $accountDescription }}</span>
                                        </span>
                                    </a>
                                </div>

                                <div class="mt-2 border-t border-slate-100 pt-2">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                                        >
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-500 transition group-hover:bg-white">
                                                <x-icon name="logout" class="h-4 w-4" />
                                            </span>
                                            Keluar
                                        </button>
                                    </form>
                                </div>
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

    <x-confirm-dialog />

    @livewireScripts
</body>
</html>
