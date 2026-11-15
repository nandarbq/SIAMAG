<x-layouts.guest>
    <div class="auth-page relative min-h-screen overflow-hidden">
        @include('auth.partials.atmosphere')

        <div class="relative z-10 flex min-h-screen flex-col px-5 pb-6 pt-5 sm:px-8 lg:px-10 xl:px-14">
            <header class="mx-auto flex w-full max-w-[88rem] items-center justify-between gap-4 border-b border-white/10 pb-5">
                <a href="{{ route('login') }}" class="group flex items-center gap-3" aria-label="SIAMAG BMKG Banyuwangi">
                    <span class="auth-logo-tile transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-xl">
                        <img src="{{ asset('images/logo-bmkg.png') }}" alt="" class="h-8 w-auto">
                    </span>
                    <span>
                        <span class="block text-sm font-semibold tracking-[0.18em] text-white">SIAMAG</span>
                        <span class="mt-0.5 block text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400">BMKG Banyuwangi</span>
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <span class="hidden items-center gap-2 text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400 sm:inline-flex">
                        <span class="auth-status-dot"></span>
                        Sistem operasional
                    </span>
                    <span class="hidden h-4 w-px bg-white/15 sm:block"></span>
                    <span class="font-mono text-[10px] uppercase tracking-[0.16em] text-slate-500">WIB / UTC+7</span>
                </div>
            </header>

            <main class="mx-auto grid w-full max-w-[88rem] flex-1 items-start gap-10 py-10 lg:grid-cols-[minmax(0,1.15fr)_minmax(24rem,28rem)] lg:items-center lg:gap-16 lg:py-14 xl:gap-24">
                @include('auth.partials.brand-panel')

                <div class="w-full max-w-md justify-self-start lg:justify-self-end">
                    @include('auth.partials.login-card')

                    <p class="mt-5 px-1 text-center text-[10px] leading-5 text-slate-500 lg:text-left">
                        Sistem Informasi Absensi &amp; Logbook Magang &middot; Stasiun Meteorologi Banyuwangi
                    </p>
                </div>
            </main>

            <footer class="mx-auto flex w-full max-w-[88rem] flex-col gap-2 border-t border-white/10 pt-4 text-[10px] text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ now()->year }} BMKG Banyuwangi. Hak cipta dilindungi.</p>
                <p class="font-mono uppercase tracking-[0.16em]">Jl. Raya Jember &middot; Banyuwangi &middot; Jawa Timur</p>
            </footer>
        </div>
    </div>
</x-layouts.guest>
