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
            <div class="card-body">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label">Latitude</label>
                        <input wire:model="office_lat" type="text" class="input" placeholder="-8.2192000">
                        @error('office_lat') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Longitude</label>
                        <input wire:model="office_lng" type="text" class="input" placeholder="114.3692000">
                        @error('office_lng') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Radius Absensi (meter)</label>
                        <input wire:model="radius_m" type="number" min="10" max="5000" class="input">
                        @error('radius_m') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Akurasi GPS Maksimal (meter)</label>
                        <input wire:model="max_accuracy_m" type="number" min="10" max="1000" class="input">
                        @error('max_accuracy_m') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div
                    x-data="{
                        locate() {
                            if (!navigator.geolocation) {
                                alert('Browser tidak mendukung geolocation.');
                                return;
                            }
                            navigator.geolocation.getCurrentPosition((position) => {
                                $wire.set('office_lat', position.coords.latitude.toFixed(7));
                                $wire.set('office_lng', position.coords.longitude.toFixed(7));
                            }, () => alert('Gagal mendeteksi lokasi. Pastikan izin lokasi aktif.'), { enableHighAccuracy: true });
                        }
                    }"
                    class="mt-5 flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50/60 p-4"
                >
                    <x-icon name="map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-brand-900">Gunakan lokasi perangkat saat ini</p>
                        <p class="mt-0.5 text-xs text-brand-700">Buka aplikasi dari lokasi kantor, lalu klik tombol untuk mengisi koordinat otomatis.</p>
                    </div>
                    <button type="button" @click="locate()" class="btn-primary btn-sm shrink-0">Deteksi</button>
                </div>
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
                        <span class="stat-icon bg-violet-50 text-violet-600">
                            <x-icon name="shield" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Validasi Absensi</h2>
                            <p class="text-xs text-slate-500">Kontrol persyaratan saat mahasiswa absen.</p>
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
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 px-4 py-3">
                        <input wire:model="require_geofence" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-700">Wajib dalam radius kantor</span>
                            <span class="block text-xs text-slate-500">Absensi ditolak jika lokasi berada di luar radius yang ditentukan.</span>
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
