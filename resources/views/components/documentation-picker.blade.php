@props([
    'label' => 'Dokumentasi',
    'inputName' => 'photo',
    'existingUrl' => null,
    'required' => false,
])

<div
    x-data="documentationPicker({ inputName: @js($inputName), existingUrl: @js($existingUrl) })"
    x-on:keydown.escape.window="close()"
    {{ $attributes->merge(['class' => 'space-y-2']) }}
>
    <label class="form-label">
        {{ $label }}
        @if ($required)
            <span class="font-normal text-rose-500">(wajib)</span>
        @endif
    </label>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <template x-if="hasSelection">
            <a :href="selectedUrl || existingUrl" target="_blank" class="block shrink-0">
                <img :src="selectedUrl || existingUrl" alt="Dokumentasi logbook" class="h-16 w-16 rounded-xl object-cover ring-1 ring-slate-200">
            </a>
        </template>

        <div class="min-w-0 flex-1">
            <button type="button" x-on:click="openPicker()" class="btn-secondary w-full justify-start">
                <x-icon name="paperclip" class="h-4 w-4 shrink-0 text-brand-600" />
                <span x-text="hasSelection ? 'Ganti Dokumentasi' : 'Tambah Dokumentasi'"></span>
            </button>

            <div class="mt-2 flex items-center gap-2 text-xs">
                <span x-show="selectedName" x-text="selectedName" class="min-w-0 truncate font-semibold text-slate-700"></span>
                <span x-show="! selectedName && existingUrl" class="font-semibold text-slate-700">Dokumentasi sudah tersimpan.</span>
                <span x-show="! selectedName && ! existingUrl" class="text-slate-400">Belum ada dokumentasi dipilih.</span>
                <span x-show="uploading" class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-brand-200 border-t-brand-600"></span>
            </div>
        </div>
    </div>

    <input
        x-ref="fileInput"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        class="sr-only"
        x-on:change="onFileSelected($event)"
    >
    <input
        x-ref="systemCameraInput"
        type="file"
        accept="image/*"
        capture="environment"
        class="sr-only"
        x-on:change="onFileSelected($event)"
    >

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:items-center sm:p-4"
        x-on:click.self="close()"
        role="dialog"
        aria-modal="true"
        aria-label="Pilih dokumentasi"
    >
        <div class="max-h-[calc(100dvh-1rem)] w-full max-w-lg overflow-y-auto overscroll-contain rounded-t-3xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl shadow-slate-950/30 sm:max-h-[calc(100dvh-2rem)] sm:rounded-3xl sm:p-6 sm:pb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600">Dokumentasi</p>
                    <h2 class="mt-1 text-lg font-bold tracking-tight text-slate-900">Pilih dokumentasi</h2>
                    <p class="mt-1 text-xs text-slate-500">Ambil langsung dengan kamera atau pilih dari galeri.</p>
                </div>
                <button
                    type="button"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 sm:h-9 sm:w-9"
                    x-on:click="close()"
                    x-bind:disabled="uploading"
                    aria-label="Tutup"
                >
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div x-show="view === 'menu'" class="mt-5 grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    x-on:click="startCamera()"
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left transition hover:border-brand-300 hover:bg-brand-50/50"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-slate-200">
                        <x-icon name="camera" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-800">Ambil dengan kamera</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Depan/belakang bisa dibalik</span>
                    </span>
                </button>

                <button
                    type="button"
                    x-on:click="chooseFile()"
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left transition hover:border-brand-300 hover:bg-brand-50/50"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-slate-200">
                        <x-icon name="arrow-up-circle" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-800">Pilih dari galeri</span>
                        <span class="mt-0.5 block text-xs text-slate-500">JPG, PNG, WEBP maksimal 4 MB</span>
                    </span>
                </button>
            </div>

            <div x-show="view === 'camera'" class="mt-5">
                <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-slate-950 ring-1 ring-slate-900/10">
                    <video
                        x-ref="video"
                        autoplay
                        muted
                        playsinline
                        class="absolute inset-0 h-full w-full object-cover"
                        x-bind:class="facingMode === 'user' ? 'scale-x-[-1]' : ''"
                    ></video>
                    <canvas x-ref="canvas" class="hidden"></canvas>

                    <span x-show="cameraReady" x-text="cameraLabel" class="absolute left-3 top-3 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur"></span>

                    <button
                        type="button"
                        x-show="multipleCameras && cameraReady"
                        x-on:click="flipCamera()"
                        class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-950/70 text-white backdrop-blur transition hover:bg-slate-950/85"
                        x-bind:aria-label="facingMode === 'environment' ? 'Balik ke kamera depan' : 'Balik ke kamera belakang'"
                        title="Balik kamera"
                    >
                        <x-icon name="switch-camera" class="h-5 w-5" />
                    </button>

                    <div x-show="! cameraReady && ! error" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-white">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Menyiapkan kamera...
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center gap-3">
                    <button type="button" x-on:click="close()" class="btn-secondary btn-sm">Batal</button>
                    <button
                        type="button"
                        x-on:click="capture()"
                        x-bind:disabled="! cameraReady"
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-600/30 ring-4 ring-brand-100 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Ambil gambar"
                    >
                        <x-icon name="camera" class="h-6 w-6" />
                    </button>
                    <span class="w-16 text-xs text-slate-500">Ambil gambar</span>
                </div>
            </div>

            <div x-show="view === 'preview'" class="mt-5">
                <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-slate-950 ring-1 ring-slate-900/10">
                    <img :src="selectedUrl" alt="Pratinjau dokumentasi" class="h-full w-full object-cover">
                </div>
                <p x-show="selectedName" x-text="selectedName" class="mt-2 truncate text-xs font-semibold text-slate-700"></p>

                <div x-show="uploading" class="mt-4">
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-600 transition-all duration-200" x-bind:style="'width: ' + progress + '%'"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Menyimpan dokumentasi...</p>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="retake()" x-bind:disabled="uploading" class="btn-secondary">
                        <x-icon name="refresh" class="h-4 w-4" />
                        Ambil ulang
                    </button>
                    <button type="button" x-on:click="usePhoto()" x-bind:disabled="uploading" class="btn-primary">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        <span x-show="! uploading">Gunakan dokumentasi</span>
                        <span x-show="uploading">Menyimpan...</span>
                    </button>
                </div>
            </div>

            <p x-show="error" x-text="error" class="form-error mt-4"></p>
            <div x-show="error" class="mt-2 flex flex-wrap gap-2">
                <button type="button" x-show="view === 'camera'" x-on:click="startCamera()" class="btn-secondary btn-sm">
                    <x-icon name="refresh" class="h-4 w-4" />
                    Coba lagi
                </button>
                <button type="button" x-show="view === 'camera'" x-on:click="openSystemCamera()" class="btn-secondary btn-sm">
                    <x-icon name="camera" class="h-4 w-4" />
                    Kamera sistem
                </button>
                <button type="button" x-on:click="chooseFile()" class="btn-secondary btn-sm">
                    <x-icon name="arrow-up-circle" class="h-4 w-4" />
                    Pilih dari galeri
                </button>
            </div>
        </div>
    </div>
</div>
