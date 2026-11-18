@props(['label' => 'Ganti foto', 'triggerClass' => 'btn-secondary'])

<div
    x-data="avatarCropper"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
    x-on:keydown.escape.window="close()"
    class="inline-flex"
>
    <input
        x-ref="input"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        class="sr-only"
        x-on:change="onFileSelected($event)"
    >

    <button type="button" {{ $attributes->merge(['class' => $triggerClass]) }} x-on:click="chooseFile()">
        <x-icon name="camera" class="h-4 w-4" />
        {{ $label }}
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:items-center sm:p-4"
        x-on:click.self="close()"
        role="dialog"
        aria-modal="true"
        aria-label="Sesuaikan foto profil"
    >
        <div
            x-show="open"
            x-transition
            class="w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl shadow-slate-950/30 sm:rounded-3xl sm:p-6"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600">Foto Profil</p>
                    <h2 class="mt-1 text-lg font-bold tracking-tight text-slate-900">Sesuaikan foto</h2>
                    <p class="mt-1 text-xs text-slate-500">Geser foto untuk mengatur posisi, lalu atur ukuran dengan slider.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    x-on:click="close()"
                    x-bind:disabled="uploading"
                    aria-label="Tutup"
                >
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="avatar-cropper mt-5 aspect-square w-full overflow-hidden rounded-2xl bg-slate-950 ring-1 ring-slate-900/10">
                <img x-ref="image" alt="Pratinjau foto profil" class="block max-w-full">
            </div>

            <div class="mt-5 flex items-center gap-3">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Perkecil</span>
                <input
                    type="range"
                    min="1"
                    max="3"
                    step="0.01"
                    :value="zoomLevel"
                    x-on:input="zoom($event)"
                    x-bind:disabled="!cropper || uploading"
                    class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
                    aria-label="Ukuran foto"
                >
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Perbesar</span>
            </div>

            <p x-show="error" x-text="error" class="form-error mt-3"></p>
            @error('photo') <p class="form-error mt-3">{{ $message }}</p> @enderror

            <div x-show="uploading" class="mt-4">
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-600 transition-all duration-200" :style="'width: ' + progress + '%'"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Menyimpan foto...</p>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn-secondary" x-on:click="close()" x-bind:disabled="uploading">
                    Batal
                </button>
                <button type="button" class="btn-primary" x-on:click="save()" x-bind:disabled="uploading || !cropper">
                    <x-icon name="check-circle" class="h-4 w-4" />
                    <span x-show="!uploading">Simpan foto</span>
                    <span x-show="uploading">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>
