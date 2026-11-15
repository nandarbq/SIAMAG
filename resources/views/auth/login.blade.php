<x-layouts.guest>
    @php
        $loginModalOpen = $errors->any() || session('status');
    @endphp

    <div
        x-data="{ loginOpen: {{ $loginModalOpen ? 'true' : 'false' }} }"
        x-effect="document.body.classList.toggle('overflow-hidden', loginOpen)"
        data-login-modal-open="{{ $loginModalOpen ? 'true' : 'false' }}"
    >
        <div class="auth-page relative min-h-screen overflow-hidden" :inert="loginOpen" :aria-hidden="loginOpen ? 'true' : 'false'">
            @include('auth.partials.atmosphere')

            <div class="relative z-10 flex min-h-screen flex-col px-5 pb-6 pt-5 sm:px-8 lg:px-10 xl:px-14">
                <header class="mx-auto flex w-full max-w-[88rem] items-center justify-between gap-4 border-b border-white/10 pb-5">
                    <a href="{{ route('login') }}" class="group flex items-center gap-3" aria-label="SIAMAG BMKG Banyuwangi">
                        <span class="auth-logo-tile transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-xl">
                            <img src="{{ asset('images/logo-bmkg.png') }}" alt="" class="h-8 w-auto">
                        </span>
                        <span>
                            <span class="block text-sm font-semibold tracking-[0.16em] text-white">SIAMAG</span>
                            <span class="mt-0.5 block text-xs text-slate-400">BMKG Banyuwangi</span>
                        </span>
                    </a>

                    <div class="flex items-center gap-3">
                        <p class="hidden max-w-xs text-right text-xs leading-5 text-slate-400 xl:block">
                            Sistem Informasi Absensi &amp; Logbook Magang
                        </p>
                        <button
                            type="button"
                            class="auth-cta"
                            x-ref="loginTrigger"
                            @click="loginOpen = true"
                        >
                            Masuk
                        </button>
                    </div>
                </header>

                <main class="mx-auto flex w-full max-w-[88rem] flex-1 items-center py-10 lg:py-14">
                    @include('auth.partials.brand-panel')
                </main>

                <footer class="mx-auto flex w-full max-w-[88rem] flex-col gap-2 border-t border-white/10 pt-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ now()->year }} BMKG Banyuwangi</p>
                    <p>Jl. Raya Jember, Banyuwangi, Jawa Timur</p>
                </footer>
            </div>
        </div>

        @include('auth.partials.login-modal')
    </div>
</x-layouts.guest>
