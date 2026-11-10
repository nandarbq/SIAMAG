<x-layouts.guest>
    <div class="flex min-h-screen flex-col bg-slate-100">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-5 py-6 sm:px-8">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-700 text-white">
                    <x-icon name="building" class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-bold tracking-tight text-slate-900">SIAMAG</p>
                    <p class="text-xs text-slate-500">BMKG Banyuwangi</p>
                </div>
            </div>
            <p class="hidden text-xs font-medium text-slate-500 sm:block">Sistem Informasi Absensi &amp; Logbook Magang</p>
        </header>

        <main class="flex flex-1 items-start justify-center px-5 pb-16 pt-4 sm:items-center sm:pt-0">
            <div class="w-full max-w-sm">
                <div class="card p-7 sm:p-8">
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Masuk</h1>
                    <p class="mt-1 text-sm text-slate-500">Gunakan akun yang diberikan admin.</p>

                    @if (session('status'))
                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4" x-data="{ showPassword: false }">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@bmkg.go.id"
                                class="input @error('email') border-rose-300 focus:border-rose-500 focus:ring-rose-500/10 @enderror"
                            >
                            @error('email')
                                <p class="form-error flex items-center gap-1.5">
                                    <x-icon name="alert" class="h-3.5 w-3.5" />
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Masukkan password"
                                    class="input pr-11 @error('password') border-rose-300 focus:border-rose-500 focus:ring-rose-500/10 @enderror"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                >
                                    <x-icon name="eye" class="h-4 w-4" />
                                </button>
                            </div>
                            @error('password')
                                <p class="form-error flex items-center gap-1.5">
                                    <x-icon name="alert" class="h-3.5 w-3.5" />
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 pt-1">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                                Ingat saya
                            </label>
                            <span class="text-xs text-slate-400">Lupa password? Hubungi admin.</span>
                        </div>

                        <button type="submit" class="btn-primary w-full py-2.5">
                            Masuk
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-500">&copy; {{ now()->year }} BMKG Banyuwangi</p>
            </div>
        </main>
    </div>
</x-layouts.guest>
