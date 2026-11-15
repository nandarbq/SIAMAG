<section class="relative">
    <div class="auth-reveal lg:hidden">
        <p class="auth-kicker">
            <span class="auth-kicker-line"></span>
            Stasiun Meteorologi Banyuwangi
        </p>

        <h1 class="mt-5 text-4xl font-semibold leading-[0.95] tracking-[-0.055em] text-white sm:text-5xl">
            Magang yang
            <span class="auth-display-accent block">terukur.</span>
        </h1>

        <p class="mt-4 max-w-md text-sm leading-6 text-slate-300">
            Catat kehadiran, susun logbook, dan pantau progres magang Anda dalam satu ruang kerja.
        </p>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <span class="auth-live-badge">
                <span class="auth-status-dot"></span>
                Stasiun terhubung
            </span>
            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.16em] text-slate-400">
                WIB / UTC+7
            </span>
        </div>
    </div>

    <div class="relative hidden lg:block">
        <div class="auth-radar absolute -right-24 top-1/2 -translate-y-1/2 xl:-right-16" aria-hidden="true">
            <div class="auth-radar-cross"></div>
            <span class="auth-radar-dot left-[62%] top-[28%]"></span>
            <span class="auth-radar-dot left-[38%] top-[64%]"></span>
            <span class="auth-radar-dot left-[72%] top-[58%]"></span>
        </div>

        <div class="relative max-w-xl">
            <p class="auth-kicker auth-reveal">
                <span class="auth-kicker-line"></span>
                Stasiun Meteorologi Banyuwangi
            </p>

            <h1 class="auth-display auth-reveal auth-reveal--delay-1 mt-7">
                Magang yang<br>
                <span class="auth-display-accent">terukur.</span>
            </h1>

            <p class="auth-reveal auth-reveal--delay-2 mt-7 max-w-md text-sm leading-7 text-slate-300">
                Satu ruang kerja untuk mencatat kehadiran, menyusun logbook, dan membaca progres magang Anda di BMKG Banyuwangi.
            </p>

            <div class="auth-reveal auth-reveal--delay-3 mt-9 grid gap-3 sm:grid-cols-3">
                <div class="auth-feature">
                    <div class="flex items-center justify-between gap-3">
                        <span class="auth-feature-index">01</span>
                        <x-icon name="camera" class="h-4 w-4 text-brand-300" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-white">Absensi presisi</p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">Foto &amp; titik lokasi</p>
                </div>

                <div class="auth-feature">
                    <div class="flex items-center justify-between gap-3">
                        <span class="auth-feature-index">02</span>
                        <x-icon name="book" class="h-4 w-4 text-accent-300" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-white">Logbook rapi</p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">Catatan harian</p>
                </div>

                <div class="auth-feature">
                    <div class="flex items-center justify-between gap-3">
                        <span class="auth-feature-index">03</span>
                        <x-icon name="chart" class="h-4 w-4 text-emerald-300" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-white">Progres terlihat</p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">Laporan siap unduh</p>
                </div>
            </div>

            <div class="auth-telemetry auth-reveal auth-reveal--delay-3 mt-8 max-w-xl">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="auth-label">Observasi stasiun</p>
                        <p class="mt-1.5 font-mono text-xs text-slate-300">-8.2192&deg; S / 114.3692&deg; E</p>
                    </div>
                    <span class="auth-live-badge">
                        <span class="auth-status-dot"></span>
                        Terhubung
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 border-t border-white/10 pt-4 sm:grid-cols-3">
                    <div>
                        <p class="auth-label">Tanggal</p>
                        <p class="mt-1 text-sm font-semibold text-white">{{ now()->translatedFormat('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="auth-label">Zona waktu</p>
                        <p class="mt-1 text-sm font-semibold text-white">WIB / UTC+7</p>
                    </div>
                    <div>
                        <p class="auth-label">Status</p>
                        <p class="mt-1 text-sm font-semibold text-emerald-300">Operasional</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
