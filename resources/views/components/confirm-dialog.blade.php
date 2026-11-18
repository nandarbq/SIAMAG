<div
    x-data="confirmDialog"
    x-cloak
    x-bind:class="open ? 'pointer-events-auto' : 'pointer-events-none'"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
    @app-confirm.window="show($event.detail)"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-[80] overflow-y-auto"
    :aria-hidden="open ? 'false' : 'true'"
    :inert="! open"
    role="dialog"
    aria-modal="true"
    aria-labelledby="app-confirm-title"
    aria-describedby="app-confirm-message"
>
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm motion-reduce:transition-none"
        @click="close()"
    ></div>

    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6" @click.self="close()">
        <div
            x-show="open"
            x-transition:enter="transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]"
            x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
            class="relative w-full max-w-md overflow-hidden rounded-t-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/5 will-change-transform motion-reduce:transition-none sm:rounded-3xl"
        >
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <x-icon name="trash" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-rose-600">Konfirmasi</p>
                    <h2 id="app-confirm-title" x-text="title" class="mt-1 text-lg font-bold tracking-tight text-slate-900"></h2>
                    <p id="app-confirm-message" x-text="message" class="mt-2 text-sm leading-relaxed text-slate-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-ref="cancel"
                    x-bind:disabled="working"
                    @click="close()"
                    class="btn-secondary w-full sm:w-auto"
                    x-text="cancelLabel"
                ></button>
                <button
                    type="button"
                    x-bind:disabled="working"
                    @click="confirm()"
                    class="btn-danger w-full sm:w-auto"
                >
                    <span x-show="! working" class="inline-flex items-center gap-2">
                        <x-icon name="trash" class="h-4 w-4" />
                        <span x-text="confirmLabel"></span>
                    </span>
                    <span x-show="working" class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        <span x-text="workingLabel"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
