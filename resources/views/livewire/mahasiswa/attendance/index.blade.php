<div class="space-y-6">
    <section class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="page-eyebrow">Kehadiran</p>
            <h1 class="page-title">Absensi Harian</h1>
            <p class="page-subtitle">
                @if ($requireGeofence)
                    Ambil selfie dan pastikan lokasi Anda berada di dalam area kantor.
                @else
                    Ambil selfie untuk mencatat kehadiran hari ini.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <x-icon name="calendar" class="h-4 w-4" />
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
    </section>

    @if (! $profile)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Profil magang belum lengkap. Hubungi admin sebelum melakukan absensi.</p>
        </div>
    @endif

    @error('attendance')
        <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>{{ $message }}</p>
        </div>
    @enderror

    @error('photo')
        <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>{{ $message }}</p>
        </div>
    @enderror

    @php
        $onLeave = $today && in_array($today->status->value, ['izin', 'sakit', 'libur'], true);
        $finished = $today?->check_in_at && $today?->check_out_at;
    @endphp

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <div
            class="card overflow-hidden"
            x-data="{
                locating: false,
                ready: false,
                error: null,
                distance: null,
                accuracy: null,
                officeLat: {{ $officeLat }},
                officeLng: {{ $officeLng }},
                radius: {{ $radius }},
                maxAccuracy: {{ $maxAccuracy }},
                requireGeofence: @js($requireGeofence),
                init() {
                    if (this.requireGeofence) {
                        this.locate();
                    }
                },
                locate() {
                    if (! this.requireGeofence) {
                        return;
                    }

                    this.locating = true;
                    this.ready = false;
                    this.error = null;

                    if (! navigator.geolocation) {
                        this.locating = false;
                        this.error = 'Perangkat ini tidak mendukung layanan lokasi.';
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const coords = position.coords;
                            this.accuracy = Math.round(coords.accuracy);
                            this.distance = Math.round(this.haversine(coords.latitude, coords.longitude, this.officeLat, this.officeLng));

                            this.$refs.lat.value = coords.latitude;
                            this.$refs.lng.value = coords.longitude;
                            this.$refs.accuracy.value = this.accuracy;

                            ['lat', 'lng', 'accuracy'].forEach((key) => {
                                this.$refs[key].dispatchEvent(new Event('input', { bubbles: true }));
                            });

                            this.ready = true;
                            this.locating = false;
                        },
                        (failure) => {
                            this.locating = false;
                            this.error = failure.code === 1
                                ? 'Izin lokasi ditolak. Aktifkan izin lokasi pada browser lalu coba lagi.'
                                : 'Lokasi tidak terdeteksi. Pastikan GPS aktif dan Anda berada di area terbuka.';
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                    );
                },
                haversine(lat1, lng1, lat2, lng2) {
                    const toRad = (value) => (value * Math.PI) / 180;
                    const earth = 6371000;
                    const dLat = toRad(lat2 - lat1);
                    const dLng = toRad(lng2 - lng1);
                    const a = Math.sin(dLat / 2) ** 2
                        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;

                    return earth * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                },
            }"
        >
            <div class="card-header">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Ambil Kehadiran</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        @if ($requireGeofence)
                            Foto wajah dan lokasi GPS diperlukan untuk validasi.
                        @else
                            Foto wajah diperlukan untuk mencatat kehadiran.
                        @endif
                    </p>
                </div>
                @if ($today)
                    <x-attendance-status :status="$today->status" />
                @else
                    <span class="badge-slate">Belum absen</span>
                @endif
            </div>

            <div class="card-body space-y-5">
                <input type="hidden" wire:model="lat" x-ref="lat">
                <input type="hidden" wire:model="lng" x-ref="lng">
                <input type="hidden" wire:model="accuracy" x-ref="accuracy">

                @if ($onLeave)
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                            <x-icon name="calendar" class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-sky-900">Tercatat {{ $today->status->label() }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-sky-700">Anda tidak perlu melakukan absensi pada hari ini.</p>
                    </div>
                @elseif ($finished)
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <x-icon name="check-circle" class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-emerald-900">Absensi hari ini selesai</p>
                        <p class="mt-1 text-xs text-emerald-700">Masuk {{ $today->check_in_at?->format('H:i') }} · Pulang {{ $today->check_out_at?->format('H:i') }} WIB</p>
                    </div>
                @else
                    <div x-data="attendanceCamera({ requireSelfie: @js($requireSelfie) })" class="space-y-5">
                        <div class="space-y-3">
                            <div class="relative flex min-h-56 items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-950/90 p-2">
                            <template x-if="capturedUrl">
                                <img :src="capturedUrl" alt="Pratinjau selfie" class="absolute inset-0 h-full w-full object-cover">
                            </template>

                            <video x-show="! capturedUrl" x-ref="video" autoplay muted playsinline class="absolute inset-0 h-full w-full object-cover"></video>
                            <canvas x-ref="canvas" class="hidden"></canvas>

                            <div x-show="! cameraReady && ! capturedUrl && ! cameraError" class="relative flex flex-col items-center gap-2 text-center text-xs font-semibold text-white">
                                <span class="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Menyiapkan kamera...
                            </div>

                            <div x-show="cameraError" class="relative mx-4 rounded-2xl bg-rose-50/95 px-4 py-3 text-center text-xs font-medium leading-relaxed text-rose-700 shadow-sm">
                                <p x-text="cameraError"></p>
                            </div>

                            <div x-show="uploading" class="absolute inset-x-0 bottom-0 bg-slate-950/70 px-4 py-3 text-center text-xs font-semibold text-white backdrop-blur-sm">
                                Mengunggah foto... <span x-text="uploadProgress + '%'"></span>
                            </div>

                            <span x-show="capturedUrl && ! uploading" class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">
                                Selfie siap digunakan
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs leading-relaxed text-slate-500">Gunakan kamera depan, pastikan wajah terlihat jelas dan pencahayaan cukup.</p>

                            <div class="flex gap-2">
                                <button type="button" x-show="cameraReady && ! capturedUrl" @click="capture()" class="btn-primary btn-sm">
                                    <x-icon name="camera" class="h-4 w-4" />
                                    Ambil Foto
                                </button>
                                <button type="button" x-show="! cameraReady && ! capturedUrl" @click="startCamera()" class="btn-secondary btn-sm">
                                    <x-icon name="refresh" class="h-4 w-4" />
                                    Coba Lagi
                                </button>
                                <button type="button" x-show="cameraError && capturedUrl && ! uploading" @click="retryUpload()" class="btn-primary btn-sm">
                                    <x-icon name="refresh" class="h-4 w-4" />
                                    Unggah Ulang
                                </button>
                                <button type="button" x-show="capturedUrl && ! uploading" @click="retake()" class="btn-secondary btn-sm">
                                    <x-icon name="refresh" class="h-4 w-4" />
                                    Ambil Ulang
                                </button>
                            </div>
                        </div>
                    </div>

                    @if ($requireGeofence)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                    <x-icon name="map-pin" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Lokasi GPS</p>
                                        <span
                                            class="badge"
                                            :class="error ? 'badge-red' : (ready ? (distance <= radius ? 'badge-green' : 'badge-red') : 'badge-slate')"
                                        >
                                            <span x-text="error ? 'Bermasalah' : (ready ? (distance <= radius ? 'Dalam radius' : 'Di luar radius') : 'Menunggu')"></span>
                                        </span>
                                    </div>

                                    <p x-show="locating" class="mt-2 text-sm font-medium text-slate-600">Mendeteksi lokasi Anda...</p>
                                    <p x-show="! locating && error" x-text="error" class="mt-2 text-xs leading-relaxed text-rose-600"></p>
                                    <p x-show="ready && ! error" class="mt-2 text-xs leading-relaxed text-slate-600">
                                        Akurasi <span class="font-semibold text-slate-800" x-text="'± ' + accuracy + ' m'"></span>
                                        · Jarak <span class="font-semibold text-slate-800" x-text="distance + ' m'"></span>
                                        dari kantor
                                    </p>
                                    <p x-show="ready && accuracy > maxAccuracy" class="mt-1 text-xs font-semibold text-amber-600">
                                        Akurasi GPS melebihi batas {{ $maxAccuracy }} m. Coba di area terbuka.
                                    </p>
                                </div>
                                <button type="button" @click="locate()" class="btn-ghost btn-sm shrink-0" title="Perbarui lokasi">
                                    <x-icon name="refresh" class="h-4 w-4" />
                                    <span class="hidden sm:inline">Perbarui</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sky-800">
                            <x-icon name="shield" class="mt-0.5 h-5 w-5 shrink-0" />
                            <div>
                                <p class="text-sm font-semibold">Validasi radius sedang dinonaktifkan admin</p>
                                <p class="mt-1 text-xs leading-relaxed">Anda tetap bisa melakukan absensi dari lokasi mana pun. GPS tidak diwajibkan.</p>
                            </div>
                        </div>
                    @endif

                        <div class="flex flex-col gap-2 border-t border-slate-100 pt-5">
                            @if (! $today?->check_in_at)
                                <button type="button" wire:click="checkIn" wire:loading.attr="disabled" wire:target="checkIn" x-bind:disabled="uploading || ! canSubmit()" class="btn-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                                    <x-icon name="camera" class="h-4 w-4" />
                                    <span wire:loading.remove wire:target="checkIn">Absen Masuk</span>
                                    <span wire:loading wire:target="checkIn">Menyimpan absensi...</span>
                                </button>
                            @elseif (! $today?->check_out_at)
                                <button type="button" wire:click="checkOut" wire:loading.attr="disabled" wire:target="checkOut" x-bind:disabled="uploading || ! canSubmit()" class="btn-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                                    <x-icon name="logout" class="h-4 w-4" />
                                    <span wire:loading.remove wire:target="checkOut">Absen Pulang</span>
                                    <span wire:loading wire:target="checkOut">Menyimpan absensi...</span>
                                </button>
                            @endif
                            <p x-show="! canSubmit()" class="text-center text-xs font-medium text-amber-600">Ambil foto selfie terlebih dahulu.</p>
                            <p class="text-center text-[11px] text-slate-400">
                                Jam kerja {{ \App\Models\Setting::get('work_start', '07:30') }} — {{ \App\Models\Setting::get('work_end', '16:00') }} WIB.
                                @if ($requireGeofence)
                                    Radius kantor {{ $radius }} m.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Ringkasan Hari Ini</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ now()->translatedFormat('d F Y') }}</p>
                    </div>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-600/10">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">Masuk</p>
                            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $today?->check_in_at?->format('H:i') ?? '--:--' }}</p>
                            <p class="mt-1 text-[11px] text-emerald-700">{{ $today?->check_in_at?->translatedFormat('d M Y') ?? 'Belum absen' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-500/10">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Pulang</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $today?->check_out_at?->format('H:i') ?? '--:--' }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $today?->check_out_at?->translatedFormat('d M Y') ?? 'Belum absen' }}</p>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-slate-50/60">
                        <div class="flex items-center justify-between px-4 py-3 text-sm">
                            <span class="text-slate-500">Status</span>
                            @if ($today)
                                <x-attendance-status :status="$today->status" />
                            @else
                                <span class="badge-slate">Belum absen</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between px-4 py-3 text-sm">
                            <span class="text-slate-500">Keterlambatan</span>
                            <span class="font-semibold text-slate-800">{{ $today?->late_minutes ? $today->late_minutes.' menit' : '—' }}</span>
                        </div>
                        @if ($requireGeofence)
                            <div class="flex items-center justify-between px-4 py-3 text-sm">
                                <span class="text-slate-500">Jarak absen masuk</span>
                                <span class="font-semibold text-slate-800">{{ $today?->check_in_distance !== null ? $today->check_in_distance.' m' : '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-3 text-sm">
                                <span class="text-slate-500">Jarak absen pulang</span>
                                <span class="font-semibold text-slate-800">{{ $today?->check_out_distance !== null ? $today->check_out_distance.' m' : '—' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Lokasi Kantor</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            @if ($requireGeofence)
                                Titik pusat validasi absensi
                            @else
                                Informasi lokasi kantor
                            @endif
                        </p>
                    </div>
                    <span class="stat-icon bg-brand-50 text-brand-600"><x-icon name="building" class="h-5 w-5" /></span>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <p class="font-semibold text-slate-800">{{ \App\Models\Setting::get('office_name', 'BMKG Banyuwangi') }}</p>
                    <p class="text-xs leading-relaxed text-slate-500">{{ \App\Models\Setting::get('office_address', '-') }}</p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-slate-400">Latitude</p>
                            <p class="mt-0.5 font-semibold text-slate-700">{{ $officeLat }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-slate-400">Longitude</p>
                            <p class="mt-0.5 font-semibold text-slate-700">{{ $officeLng }}</p>
                        </div>
                    </div>
                    @if ($requireGeofence)
                        <div class="flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2.5 text-xs text-brand-700">
                            <x-icon name="shield" class="h-4 w-4 shrink-0" />
                            <p>Absensi valid jika berada dalam radius {{ $radius }} m dan akurasi GPS maksimal {{ $maxAccuracy }} m.</p>
                        </div>
                    @else
                        <div class="flex items-center gap-2 rounded-xl bg-sky-50 px-3 py-2.5 text-xs text-sky-700">
                            <x-icon name="shield" class="h-4 w-4 shrink-0" />
                            <p>Validasi radius sedang dinonaktifkan admin. Absensi tidak dibatasi jarak dari kantor.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="text-base font-bold text-slate-900">Riwayat Absensi</h2>
                <p class="mt-0.5 text-xs text-slate-500">10 absensi terakhir Anda</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Masuk</th>
                        <th class="table-th">Pulang</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Lokasi</th>
                        <th class="table-th text-right">Foto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($history as $attendance)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="table-td">
                                <p class="font-semibold text-slate-800">{{ $attendance->date?->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">{{ $attendance->date?->translatedFormat('l') }}</p>
                            </td>
                            <td class="table-td font-medium text-slate-700">{{ $attendance->check_in_at?->format('H:i') ?? '—' }}</td>
                            <td class="table-td font-medium text-slate-700">{{ $attendance->check_out_at?->format('H:i') ?? '—' }}</td>
                            <td class="table-td"><x-attendance-status :status="$attendance->status" /></td>
                            <td class="table-td">
                                <span class="text-xs text-slate-500">
                                    {{ $attendance->check_in_distance !== null ? $attendance->check_in_distance.' m' : '—' }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="flex justify-end gap-1.5">
                                    @if ($attendance->check_in_photo)
                                        <a href="{{ route('attendance.photo', [$attendance, 'in']) }}" target="_blank" class="flex h-9 w-9 overflow-hidden rounded-lg ring-1 ring-slate-200" title="Foto masuk">
                                            <img src="{{ route('attendance.photo', [$attendance, 'in']) }}" alt="Foto masuk" class="h-full w-full object-cover">
                                        </a>
                                    @endif
                                    @if ($attendance->check_out_photo)
                                        <a href="{{ route('attendance.photo', [$attendance, 'out']) }}" target="_blank" class="flex h-9 w-9 overflow-hidden rounded-lg ring-1 ring-slate-200" title="Foto pulang">
                                            <img src="{{ route('attendance.photo', [$attendance, 'out']) }}" alt="Foto pulang" class="h-full w-full object-cover">
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada riwayat absensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($history->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $history->links() }}</div>
        @endif
    </div>
</div>
