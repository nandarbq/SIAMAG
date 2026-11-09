<x-layouts.guest>
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
        <div class="auth-bg relative hidden overflow-hidden p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-accent-500/20 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur">
                    <x-icon name="sparkles" class="h-6 w-6 text-accent-300" />
                </div>
                <div>
                    <p class="text-lg font-bold tracking-tight">SIAMAG</p>
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-slate-300">BMKG Banyuwangi</p>
                </div>
            </div>

            <div class="relative max-w-xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-400"></span>
                    Sistem Informasi Absensi & Logbook Magang
                </p>
                <h1 class="text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                    Kelola magang lebih rapi, transparan, dan terukur.
                </h1>
                <p class="mt-5 text-base leading-relaxed text-slate-300">
                    Absensi berbasis selfie dan GPS, logbook harian, pengajuan izin, hingga laporan otomatis dalam satu platform.
                </p>

                <div class="mt-10 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <x-icon name="camera" class="h-5 w-5 text-accent-300" />
                        <p class="mt-3 text-sm font-semibold">Absensi Selfie + GPS</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">Validasi lokasi dan foto kehadiran secara real-time.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <x-icon name="chart" class="h-5 w-5 text-accent-300" />
                        <p class="mt-3 text-sm font-semibold">Rekap & Ekspor</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">Unduh laporan PDF, Excel, dan Word dengan mudah.</p>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-slate-400">
                &copy; {{ now()->year }} Stasiun Meteorologi BMKG Banyuwangi. Seluruh hak cipta dilindungi.
            </p>
        </div>

        <div class="flex items-center justify-center bg-slate-50 px-5 py-12 sm:px-10">
            <div class="w-full max-w-md animate-fade-up">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/25">
                        <x-icon name="sparkles" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-base font-bold tracking-tight text-slate-900">SIAMAG</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">BMKG Banyuwangi</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Selamat datang kembali</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Masuk ke akun Anda</h2>
                    <p class="mt-2 text-sm text-slate-500">Gunakan email dan password yang diberikan admin.</p>
                </div>

                @if (session('status'))
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" x-data="{ showPassword: false }">
                    @csrf

                    <div>
                        <label for="email" class="label">Alamat email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <x-icon name="mail" class="h-4.5 w-4.5" />
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@bmkg.go.id"
                                class="input pl-10 @error('email') border-rose-300 focus:border-rose-500 focus:ring-rose-500/10 @enderror"
                            >
                        </div>
                        @error('email')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                <x-icon name="alert" class="h-3.5 w-3.5" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <x-icon name="lock" class="h-4.5 w-4.5" />
                            </span>
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                class="input pl-10 pr-11 @error('password') border-rose-300 focus:border-rose-500 focus:ring-rose-500/10 @enderror"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            >
                                <x-icon name="eye" class="h-4.5 w-4.5" />
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                <x-icon name="alert" class="h-3.5 w-3.5" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                            Ingat saya
                        </label>
                        <span class="text-xs text-slate-400">Lupa password? Hubungi admin.</span>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3">
                        Masuk ke SIAMAG
                        <x-icon name="logout" class="h-4 w-4 rotate-180" />
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest>
