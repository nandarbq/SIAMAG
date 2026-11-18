<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Konfigurasi Sistem</p>
        <h1 class="page-title mt-1">Pengaturan</h1>
        <p class="page-subtitle">Atur identitas kantor, geofence, dan aturan absensi SIAMAG.</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="stat-icon bg-brand-50 text-brand-600">
                        <x-icon name="building" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Identitas Kantor</h2>
                        <p class="text-xs text-slate-500">Ditampilkan pada laporan dan halaman aplikasi.</p>
                    </div>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label">Nama Kantor</label>
                    <input wire:model="office_name" type="text" class="input">
                    @error('office_name') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Alamat</label>
                    <input wire:model="office_address" type="text" class="input">
                    @error('office_address') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        @php
            $mapLat = is_numeric($office_lat) ? (float) $office_lat : -8.2192;
            $mapLng = is_numeric($office_lng) ? (float) $office_lng : 114.3692;
            $mapRadius = is_numeric($radius_m) ? (int) $radius_m : 150;
        @endphp

        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="stat-icon bg-emerald-50 text-emerald-600">
                        <x-icon name="map-pin" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Lokasi & Geofence</h2>
                        <p class="text-xs text-slate-500">Titik pusat dan radius validasi absensi berbasis GPS.</p>
                    </div>
                </div>
                <a
                    href="https://www.google.com/maps?q={{ $office_lat }},{{ $office_lng }}"
                    target="_blank"
                    rel="noopener"
                    class="btn-secondary btn-sm"
                >
                    <x-icon name="map-pin" class="h-3.5 w-3.5" />
                    Buka Maps
                </a>
            </div>
            <div class="card-body" x-data="officeMapPicker({ lat: @js($mapLat), lng: @js($mapLng), radius: @js($mapRadius) })">
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label">Latitude</label>
                        <input x-ref="officeLat" x-on:input.debounce.400ms="syncFromInputs()" wire:model="office_lat" type="text" class="input" placeholder="-8.2192000">
                        @error('office_lat') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Longitude</label>
                        <input x-ref="officeLng" x-on:input.debounce.400ms="syncFromInputs()" wire:model="office_lng" type="text" class="input" placeholder="114.3692000">
                        @error('office_lng') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Radius Absensi (meter)</label>
                        <input wire:model="radius_m" x-on:input.debounce.200ms="setRadius($event.target.value)" type="number" min="10" max="5000" class="input">
                        @error('radius_m') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Akurasi GPS Maksimal (meter)</label>
                        <input wire:model="max_accuracy_m" type="number" min="10" max="1000" class="input">
                        @error('max_accuracy_m') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div wire:ignore class="mt-5">
                    <div class="relative h-80 sm:h-[420px]">
                        <div class="absolute inset-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <div x-ref="map" class="h-full w-full"></div>
                        </div>

                        <div
                            class="absolute left-3 right-16 top-3 z-30 max-w-sm"
                            x-on:click.outside="searchResults = []"
                        >
                            <label class="sr-only">Cari lokasi kantor</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <x-icon name="search" class="h-4 w-4" />
                                </span>
                                <input
                                    x-model="search"
                                    x-on:input.debounce.350ms="searchPlaces()"
                                    x-on:keydown.escape="clearSearch()"
                                    type="search"
                                    class="input h-10 bg-white/95 pl-9 pr-10 shadow-lg backdrop-blur"
                                    placeholder="Cari lokasi kantor..."
                                    autocomplete="off"
                                >
                                <button
                                    type="button"
                                    x-show="search !== ''"
                                    x-cloak
                                    x-on:click="clearSearch()"
                                    class="absolute inset-y-0 right-2 my-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Hapus pencarian"
                                >
                                    <x-icon name="x" class="h-4 w-4" />
                                </button>
                            </div>

                            <div
                                x-show="searchLoading || searchResults.length > 0 || searchError || (searchPerformed && search.length >= 3)"
                                x-cloak
                                class="absolute z-40 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                            >
                                <div x-show="searchLoading" class="flex items-center gap-2 px-4 py-3 text-xs text-slate-500">
                                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-brand-600"></span>
                                    Mencari lokasi...
                                </div>

                                <p x-show="searchError" x-text="searchError" class="px-4 py-3 text-xs font-medium text-rose-600"></p>

                                <div x-show="! searchLoading && ! searchError && searchResults.length > 0" class="max-h-72 overflow-y-auto">
                                    <template x-for="result in searchResults" :key="result.id">
                                        <button
                                            type="button"
                                            x-on:click="selectSearchResult(result)"
                                            class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-brand-50/60"
                                        >
                                            <x-icon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-slate-800" x-text="result.name"></span>
                                                <span x-show="result.detail" class="mt-0.5 block truncate text-xs text-slate-500" x-text="result.detail"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>

                                <p
                                    x-show="! searchLoading && ! searchError && searchPerformed && searchResults.length === 0"
                                    class="px-4 py-3 text-xs text-slate-500"
                                >
                                    Tidak ada hasil. Coba kata kunci lain.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            x-on:click="locateDevice()"
                            class="absolute bottom-10 left-3 z-20 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow-lg backdrop-blur transition hover:bg-white"
                        >
                            <x-icon name="map-pin" class="h-4 w-4 text-brand-600" />
                            Lokasi saya
                        </button>

                        <button
                            type="button"
                            x-show="ready && ! scrollEnabled"
                            x-cloak
                            x-on:click="activateScrollZoom()"
                            class="absolute inset-0 z-10 flex cursor-pointer items-end justify-center bg-slate-900/5 pb-4"
                            aria-label="Aktifkan peta untuk zoom scroll"
                        >
                            <span class="rounded-full border border-slate-200 bg-white/95 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-lg backdrop-blur">
                                Klik peta untuk zoom scroll
                            </span>
                        </button>

                        <div
                            x-show="! ready"
                            x-cloak
                            class="pointer-events-none absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 bg-slate-100/90 text-sm text-slate-500"
                        >
                            <span class="h-6 w-6 animate-spin rounded-full border-2 border-slate-300 border-t-brand-600"></span>
                            <span>Memuat peta...</span>
                        </div>

                        <div
                            x-show="mapError"
                            x-cloak
                            x-text="mapError"
                            class="absolute inset-x-3 bottom-3 z-30 rounded-xl border border-rose-200 bg-white/95 px-3 py-2 text-xs font-medium text-rose-600 shadow-lg"
                        ></div>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <p>Aktifkan peta untuk zoom scroll, lalu klik peta atau geser pin untuk memilih titik pusat kantor.</p>
                        <p>Peta: OpenStreetMap · Pencarian: Photon</p>
                    </div>
                </div>

                <div class="mt-5 flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50/60 p-4">
                    <x-icon name="map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-brand-900">Gunakan lokasi perangkat saat ini</p>
                        <p class="mt-0.5 text-xs text-brand-700">Buka aplikasi dari lokasi kantor, lalu klik tombol untuk mengisi koordinat dan memindahkan pin.</p>
                    </div>
                    <button type="button" @click="locateDevice()" class="btn-primary btn-sm shrink-0">Deteksi</button>
                </div>

                <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 px-4 py-3">
                    <input wire:model="require_geofence" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                    <span>
                        <span class="block text-sm font-semibold text-slate-700">Aktifkan validasi radius kantor</span>
                        <span class="block text-xs text-slate-500">Jika dimatikan, absensi tetap bisa dilakukan di luar radius dan GPS tidak diwajibkan. Nilai radius di atas diabaikan.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <span class="stat-icon bg-accent-50 text-accent-600">
                            <x-icon name="clock" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Jam Kerja</h2>
                            <p class="text-xs text-slate-500">Batas absensi dan toleransi keterlambatan.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Jam Masuk</label>
                            <input wire:model="work_start" type="time" class="input">
                            @error('work_start') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Jam Pulang</label>
                            <input wire:model="work_end" type="time" class="input">
                            @error('work_end') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Toleransi Keterlambatan (menit)</label>
                        <input wire:model="late_tolerance_min" type="number" min="0" max="120" class="input">
                        @error('late_tolerance_min') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <p class="rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                        Absensi setelah pukul {{ $work_start }} + {{ $late_tolerance_min }} menit akan ditandai terlambat.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <span class="stat-icon bg-brand-50 text-brand-600">
                            <x-icon name="calendar" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Hari Kerja</h2>
                            <p class="text-xs text-slate-500">Hari yang dihitung sebagai hari kerja untuk seluruh mahasiswa.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body space-y-3">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                <input wire:model="workdays" type="checkbox" value="{{ $value }}" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('workdays') <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    <p class="rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                        Hari libur nasional dan cuti bersama tetap dikecualikan dari perhitungan absensi.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <span class="stat-icon bg-violet-50 text-violet-600">
                            <x-icon name="shield" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Validasi Selfie</h2>
                            <p class="text-xs text-slate-500">Kontrol persyaratan foto saat mahasiswa absen.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body space-y-3">
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 px-4 py-3">
                        <input wire:model="require_selfie" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-700">Wajib selfie</span>
                            <span class="block text-xs text-slate-500">Mahasiswa harus mengambil foto saat absen masuk dan pulang.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="stat-icon bg-violet-50 text-violet-600">
                        <x-icon name="book" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Logbook</h2>
                        <p class="text-xs text-slate-500">Atur kapan mahasiswa boleh mengekspor logbook.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="max-w-xs">
                    <label class="label">Batas Ekspor Logbook (H-)</label>
                    <input wire:model="logbook_export_lead_days" type="number" min="1" max="30" class="input">
                    @error('logbook_export_lead_days') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                    Contoh: diisi 7, maka tombol export logbook aktif mulai 7 hari sebelum tanggal magang selesai.
                </p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <x-icon name="check-circle" class="h-4 w-4" />
                <span wire:loading.remove>Simpan Pengaturan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
