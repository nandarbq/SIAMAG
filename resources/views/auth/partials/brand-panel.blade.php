<section class="auth-reveal relative w-full">
    <div class="pointer-events-none absolute -right-28 top-0 h-64 w-64 opacity-25 lg:hidden" aria-hidden="true">
        <div class="auth-radar h-full w-full">
            <div class="auth-radar-cross"></div>
            <span class="auth-radar-dot left-[62%] top-[28%]"></span>
            <span class="auth-radar-dot left-[38%] top-[64%]"></span>
        </div>
    </div>

    <div class="relative z-10 grid items-center gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,32rem)] lg:gap-8 xl:gap-16">
        <div class="max-w-2xl">
            <p class="text-xs font-medium text-slate-400">
                Stasiun Meteorologi Banyuwangi
            </p>

            <h1 class="mt-4 text-4xl font-semibold leading-[1.02] tracking-[-0.04em] text-white sm:text-5xl xl:text-6xl">
                Portal Magang<br>
                <span class="text-brand-300">BMKG Banyuwangi</span>
            </h1>

            <p class="mt-5 max-w-xl text-sm leading-7 text-slate-300 sm:text-base">
                Absensi, logbook, dan laporan magang dalam satu akun.
            </p>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <button type="button" class="auth-cta auth-cta--primary" @click="loginOpen = true">
                    Masuk ke SIAMAG
                </button>
                <span class="text-xs leading-5 text-slate-500">
                    Gunakan akun yang diberikan admin.
                </span>
            </div>

            <div class="mt-10 grid gap-5 border-t border-white/10 pt-6 sm:grid-cols-3">
                <div class="flex items-start gap-2.5">
                    <x-icon name="camera" class="mt-0.5 h-4 w-4 shrink-0 text-brand-300" />
                    <div>
                        <p class="text-sm font-medium text-white">Absensi harian</p>
                        <p class="mt-0.5 text-xs text-slate-400">Kehadiran magang</p>
                    </div>
                </div>

                <div class="flex items-start gap-2.5">
                    <x-icon name="book" class="mt-0.5 h-4 w-4 shrink-0 text-brand-300" />
                    <div>
                        <p class="text-sm font-medium text-white">Logbook kegiatan</p>
                        <p class="mt-0.5 text-xs text-slate-400">Catatan harian</p>
                    </div>
                </div>

                <div class="flex items-start gap-2.5">
                    <x-icon name="chart" class="mt-0.5 h-4 w-4 shrink-0 text-brand-300" />
                    <div>
                        <p class="text-sm font-medium text-white">Laporan magang</p>
                        <p class="mt-0.5 text-xs text-slate-400">Rekap kegiatan</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-white/10 pt-5 sm:grid-cols-3">
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-slate-500">Tanggal</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ now()->translatedFormat('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-slate-500">Zona waktu</p>
                    <p class="mt-1 text-sm font-semibold text-white">WIB / UTC+7</p>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-slate-500">Lokasi</p>
                    <p class="mt-1 text-sm font-semibold text-white">Banyuwangi, Jawa Timur</p>
                </div>
            </div>
        </div>

        <div class="relative hidden items-center justify-center lg:flex" aria-hidden="true">
            <div class="auth-radar w-full max-w-[30rem]">
                <div class="auth-radar-cross"></div>
                <span class="auth-radar-dot left-[62%] top-[28%]"></span>
                <span class="auth-radar-dot left-[38%] top-[64%]"></span>
                <span class="auth-radar-dot left-[72%] top-[58%]"></span>
            </div>
        </div>
    </div>
</section>
