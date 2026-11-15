<div class="auth-card auth-reveal auth-reveal--delay-2">
    <div class="flex">
        <aside class="auth-card-spine hidden w-3 shrink-0 sm:block" aria-hidden="true"></aside>

        <div class="auth-card-paper relative flex-1 p-6 sm:p-8">
            <div class="pr-12">
                <h2 id="login-title" class="text-2xl font-semibold tracking-[-0.03em] text-slate-950">Masuk</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan akun yang diberikan admin.</p>
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
                    <label for="email" class="auth-field-label">Email</label>
                    <div class="relative">
                        <x-icon name="mail" class="auth-input-icon h-4 w-4" />
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            x-ref="loginEmail"
                            autocomplete="username"
                            placeholder="nama@bmkg.go.id"
                            class="auth-input auth-input--with-icon @error('email') border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 @enderror"
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
                            class="auth-input auth-input--with-icon pr-11 @error('password') border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 @enderror"
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
                    <span x-show="! submitting">Masuk</span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                        Memproses...
                    </span>
                </button>
            </form>

            <div class="auth-meta mt-6 pt-4 text-xs text-slate-500">
                BMKG Banyuwangi
            </div>
        </div>
    </div>
</div>
