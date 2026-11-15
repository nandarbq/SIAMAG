<div
    x-cloak
    x-show="loginOpen"
    x-transition.opacity
    x-effect="if (loginOpen) { $nextTick(() => $refs.loginEmail?.focus()) }"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="login-title"
    @keydown.escape.window="if (loginOpen) { loginOpen = false; $nextTick(() => $refs.loginTrigger?.focus()) }"
>
    <div
        class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm"
        @click="loginOpen = false; $nextTick(() => $refs.loginTrigger?.focus())"
    ></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative w-full max-w-xl">
            <button
                type="button"
                class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/80 text-slate-500 shadow-sm ring-1 ring-slate-900/5 transition hover:bg-white hover:text-slate-900"
                @click="loginOpen = false; $nextTick(() => $refs.loginTrigger?.focus())"
                aria-label="Tutup"
            >
                <x-icon name="x" class="h-4 w-4" />
            </button>

            @include('auth.partials.login-card')
        </div>
    </div>
</div>
