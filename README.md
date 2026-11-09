# SIAMAG

**Sistem Informasi Absensi dan Logbook Magang BMKG Banyuwangi.**

SIAMAG adalah aplikasi web untuk mengelola kegiatan magang di BMKG Banyuwangi, mulai dari absensi harian berbasis selfie dan GPS, pengisian logbook, pengajuan izin/sakit, hingga rekap laporan bulanan.

## Fitur

### Admin

- Dashboard ringkasan kehadiran, logbook, dan pengajuan izin.
- Manajemen data mahasiswa beserta profil magang.
- Monitoring absensi harian dan koreksi data absensi.
- Review logbook: setujui atau kembalikan untuk revisi.
- Persetujuan izin/sakit dengan pembaruan absensi otomatis.
- Pengelolaan hari libur dan pengumuman.
- Rekap bulanan per mahasiswa atau seluruh mahasiswa.
- Export laporan ke **PDF**, **Excel**, dan **Word**.
- Pengaturan kantor: koordinat, radius geofence, akurasi GPS, jam kerja, dan toleransi keterlambatan.

### Mahasiswa

- Dashboard progres magang dan ringkasan aktivitas.
- Absensi masuk/pulang dengan selfie dan validasi lokasi (geofence).
- Perhitungan otomatis status hadir, terlambat, izin, sakit, dan alpha.
- Logbook harian: simpan draf, submit untuk review, dan revisi.
- Pengajuan izin/sakit dengan lampiran.
- Profil, nomor telepon, dan perubahan password.
- Riwayat absensi, logbook, dan pengajuan izin.

### Keamanan dan validasi

- Autentikasi berbasis role (`admin`, `mahasiswa`) dengan pembatasan akses per route.
- Login dibatasi dengan rate limiting dan akun nonaktif tidak dapat masuk.
- Foto absensi dan lampiran izin disimpan secara privat dan hanya dapat diakses pemilik data atau admin.
- Validasi tanggal logbook/izin, rentang periode magang, dan benturan jadwal izin.

## Teknologi

| Komponen | Teknologi |
| --- | --- |
| Framework | Laravel 13 |
| Bahasa | PHP 8.3+ |
| UI | Livewire 4, Blade, Tailwind CSS 4, Alpine.js |
| Build tool | Vite 8 |
| Database | MariaDB/MySQL |
| Grafik | Chart.js |
| Export | barryvdh/laravel-dompdf, maatwebsite/excel, phpoffice/phpword |
| Pengolahan gambar | Intervention Image 4 |
| Testing | PHPUnit 12 |

## Kebutuhan Sistem

- PHP 8.3 atau lebih baru dengan ekstensi `gd`, `fileinfo`, `mbstring`, `openssl`, `pdo`, dan driver database.
- Composer 2.
- Node.js 20+ dan npm.
- MariaDB/MySQL (disarankan). SQLite juga dapat digunakan untuk pengembangan lokal.
- Web server lokal seperti `php artisan serve`.

## Instalasi

1. Clone repositori dan masuk ke direktori proyek.

   ```bash
   git clone https://github.com/nandarbq/SIAMAG.git
   cd SIAMAG
   ```

2. Install dependensi PHP dan JavaScript.

   ```bash
   composer install
   npm install
   ```

3. Salin konfigurasi environment dan buat application key.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Buat database `siamag`, lalu sesuaikan koneksi di `.env`.

   ```dotenv
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=siamag
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Jalankan migrasi dan seeder.

   ```bash
   php artisan migrate --seed
   ```

   Untuk mengulang dari awal (menghapus seluruh data):

   ```bash
   php artisan migrate:fresh --seed
   ```

6. Build asset frontend.

   ```bash
   npm run build
   ```

7. Jalankan aplikasi.

   ```bash
   php artisan serve
   ```

   Aplikasi tersedia di `http://localhost:8000`.

### Mode pengembangan

Gunakan dua terminal:

```bash
npm run dev
php artisan serve
```

## Akun Demo

Seeder membuat dua akun berikut:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@bmkg.go.id` | `password` |
| Mahasiswa | `mahasiswa@bmkg.go.id` | `password` |

`DemoDataSeeder` juga membuat beberapa mahasiswa contoh, riwayat absensi, logbook, dan pengajuan izin agar dashboard langsung memiliki data.

## Pengujian dan Pemeriksaan

```bash
php artisan test
npm run build
```

Test menggunakan SQLite in-memory dan tidak mengubah database pengembangan. Cakupan test mencakup autentikasi, otorisasi, absensi, logbook, izin, pengaturan, pengumuman, hari libur, laporan/export, akses file privat, serta perhitungan geofence dan progres magang.

Pemeriksaan format kode PHP:

```bash
./vendor/bin/pint --test
```

## Struktur Singkat

```text
app/
  Enums/                  Status absensi, logbook, izin, dan role
  Http/Controllers/       Autentikasi, file privat, export laporan
  Livewire/Admin/         Komponen UI admin
  Livewire/Mahasiswa/     Komponen UI mahasiswa
  Models/                 Model Eloquent
  Notifications/          Notifikasi database
  Services/               AttendanceService, GeofenceService, ReportService
database/
  migrations/             Skema database
  seeders/                Data awal dan data demo
resources/
  css/                    Tailwind CSS
  js/                     Alpine.js dan Chart.js
  views/                  Blade dan komponen Livewire
routes/web.php            Route admin, mahasiswa, dan file privat
tests/                    Unit dan feature test
```

## Catatan Operasional

- Zona waktu aplikasi diatur melalui `APP_TIMEZONE` (default `.env.example`: `Asia/Jakarta`).
- Jam kerja, radius geofence, dan akurasi GPS dapat diubah dari menu **Pengaturan** admin.
- Persetujuan izin/sakit otomatis mengisi absensi pada hari kerja dalam rentang pengajuan, kecuali hari libur atau akhir pekan.
- Foto selfie dan lampiran izin disimpan di disk `local` (`storage/app/private`) dan disajikan melalui route terproteksi.
- Export laporan mengikuti filter bulan, tahun, dan mahasiswa yang dipilih pada halaman laporan.

## Lisensi

Proyek internal BMKG Banyuwangi. Hubungi pemilik repositori untuk informasi penggunaan dan distribusi.
