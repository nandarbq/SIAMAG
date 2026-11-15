<div class="auth-card auth-reveal auth-reveal--delay-2">
    <div class="flex">
        <aside class="auth-card-spine hidden w-12 shrink-0 flex-col items-center justify-between py-6 sm:flex" aria-hidden="true">
            <span class="auth-spine-label">SIAMAG</span>
            <span class="h-10 w-px bg-white/15"></span>
            <span class="auth-spine-label">BMKG</span>
        </aside>

        <div class="auth-card-paper relative flex-1 p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span class="auth-card-badge">
                        <span class="auth-status-dot"></span>
                        Akses terverifikasi
                    </span>
                    <h2 class="mt-5 text-2xl font-semibold tracking-[-0.04em] text-slate-950 sm:text-[1.75rem]">
                        Selamat datang kembali.
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Masuk ke ruang kerja magang BMKG Banyuwangi.
                    </p>
                </div>

                <span class="hidden rounded-full border border-slate-900/10 bg-white/50 px-2.5 py-1 font-mono text-[9px] font-bold uppercase tracking-[0.18em] text-slate-400 sm:inline-flex">
                    SIAMAG / {{ now()->year }}
                </span>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('login.store') }}"
                class="mt-7 space-y-5"
                x-data="{ showPassword: false, submitting: false }"
                @submit="submitting = true"
            >
                @csrf

                <div>
                    <label for="email" class="auth-field-label">Email institusi</label>
                    <div class="relative">
                        <x-icon name="mail" class="auth-input-icon h-4 w-4" />
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@bmkg.go.id"
                            class="auth-input auth-input--with-icon @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-500/10 @enderror"
                        >
                    </div>
                    @error('email')
                        <p class="form-error mt-1.5 flex items-center gap-1.5" role="alert">
                            <x-icon name="alert" class="h-3.5 w-3.5" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="auth-field-label">Password</label>
                    <div class="relative">
                        <x-icon name="lock" class="auth-input-icon h-4 w-4" />
                        <input
                            id="password"
                            name="password"
                            type="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password"
                            class="auth-input auth-input--with-icon pr-11 @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500/10 @enderror"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-700"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            aria-controls="password"
                        >
                            <x-icon name="eye" x-show="! showPassword" class="h-4 w-4" />
                            <x-icon name="eye-off" x-show="showPassword" x-cloak class="h-4 w-4" />
                        </button>
                    </div>
                    @error('password')
                        <p class="form-error mt-1.5 flex items-center gap-1.5" role="alert">
                            <x-icon name="alert" class="h-3.5 w-3.5" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 pt-0.5">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-600/25">
                        Ingat saya
                    </label>
                    <span class="text-[11px] text-slate-400">
                        Lupa password? <span class="font-semibold text-slate-600">Hubungi admin.</span>
                    </span>
                </div>

                <button type="submit" class="auth-submit" :disabled="submitting">
                    <span x-show="! submitting" class="inline-flex items-center gap-2">
                        Masuk ke SIAMAG
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                        Memverifikasi...
                    </span>
                </button>
            </form>

            <div class="auth-meta mt-6 flex flex-col gap-3 pt-4 text-[10px] text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span class="inline-flex items-center gap-1.5 font-medium">
                    <x-icon name="shield" class="h-3.5 w-3.5 text-brand-700" />
                    Koneksi aman &amp; terenkripsi
                </span>
                <span class="font-mono uppercase tracking-[0.16em]">BMKG Banyuwangi</span>
            </div>
        </div>
    </div>
</div>
