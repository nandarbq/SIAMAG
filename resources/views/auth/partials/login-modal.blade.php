<div
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto overscroll-contain"
    :class="loginOpen ? 'pointer-events-auto' : 'pointer-events-none'"
    :aria-hidden="loginOpen ? 'false' : 'true'"
    :inert="! loginOpen"
    role="dialog"
    aria-modal="true"
    aria-labelledby="login-title"
    x-effect="if (loginOpen) { $nextTick(() => $refs.loginEmail?.focus()) }"
    @keydown.escape.window="if (loginOpen) { loginOpen = false; $nextTick(() => $refs.loginTrigger?.focus()) }"
>
    <div
        x-show="loginOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm motion-reduce:transition-none"
        @click="loginOpen = false; $nextTick(() => $refs.loginTrigger?.focus())"
    ></div>

    <div class="relative flex min-h-full items-start justify-center p-4 py-6 sm:items-center sm:p-6">
        <div
            x-show="loginOpen"
            x-transition:enter="transition duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
            x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
            class="relative w-full max-w-xl will-change-transform motion-reduce:transition-none"
        >
            <button
                type="button"
                class="absolute right-3 top-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 sm:right-4 sm:top-4 sm:h-9 sm:w-9"
                @click="loginOpen = false; $nextTick(() => $refs.loginTrigger?.focus())"
                aria-label="Tutup"
            >
                <x-icon name="x" class="h-4 w-4" />
            </button>

            @include('auth.partials.login-card')
        </div>
    </div>
</div>
