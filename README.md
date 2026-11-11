# SIAMAG

**Sistem Informasi Absensi dan Logbook Magang BMKG Banyuwangi.**

SIAMAG adalah aplikasi web untuk mengelola kegiatan magang di BMKG Banyuwangi. Admin mengelola data mahasiswa, memantau absensi, mereview logbook, menyetujui izin/sakit, dan membuat laporan. Mahasiswa menggunakan aplikasi untuk absen, mengisi logbook, mengajukan izin, dan melihat profil magang.

Aplikasi ini tidak memiliki halaman registrasi mandiri. Akun mahasiswa dibuat dan dikelola oleh admin.

## Peran Pengguna

| Peran | Akses utama |
| --- | --- |
| **Admin** | Mengelola mahasiswa, absensi, logbook, izin/sakit, hari libur, pengumuman, laporan, dan pengaturan kantor. |
| **Mahasiswa** | Melakukan absensi, mengisi logbook, mengajukan izin/sakit, dan memperbarui profil. |

Akun yang dinonaktifkan admin tidak dapat login atau mengakses halaman aplikasi.

## Alur Penggunaan

1. Admin membuat akun mahasiswa dan melengkapi profil magang: institusi, jurusan, semester, periode magang, dan pembimbing lapangan.
2. Mahasiswa login, lalu melakukan absen masuk dan absen pulang dengan selfie serta validasi lokasi GPS.
3. Mahasiswa mengisi logbook harian, menyimpannya sebagai draf, lalu mengirimkannya untuk direview.
4. Admin menyetujui atau mengembalikan logbook untuk revisi. Catatan review terlihat oleh mahasiswa.
5. Mahasiswa dapat mengajukan izin atau sakit. Jika disetujui, absensi pada hari kerja dalam periode pengajuan otomatis diperbarui.
6. Admin memantau rekap bulanan dan mengekspor laporan ke PDF, Excel, atau Word.

## Fitur

### Administrator

- Dashboard berisi ringkasan kehadiran hari ini, statistik mahasiswa, logbook, dan pengajuan izin.
- Manajemen mahasiswa: tambah, lihat detail, edit, aktif/nonaktifkan, dan hapus data.
- Profil magang mahasiswa: institusi, jurusan, semester, tanggal mulai/selesai, pembimbing, dan nomor telepon pembimbing.
- Monitoring absensi dengan filter tanggal, status, dan pencarian mahasiswa.
- Melihat detail absensi, termasuk foto selfie, lokasi, jarak dari kantor, dan akurasi GPS.
- Koreksi absensi: ubah status, jam masuk/pulang, dan tambahkan catatan.
- Review logbook: setujui atau tolak dengan catatan revisi.
- Persetujuan izin/sakit: setujui atau tolak dengan alasan.
- Pengelolaan hari libur per tahun.
- Pengelolaan pengumuman: tambah, edit, aktif/nonaktifkan, dan hapus.
- Laporan bulanan per mahasiswa atau seluruh mahasiswa, lengkap dengan persentase kehadiran dan jumlah logbook disetujui.
- Export laporan ke **PDF**, **Excel**, dan **Word**.
- Pengaturan kantor: nama, alamat, koordinat, radius geofence, batas akurasi GPS, jam kerja, toleransi keterlambatan, kewajiban selfie, dan kewajiban validasi lokasi.

### Mahasiswa

- Dashboard berisi progres magang, absensi hari ini, statistik bulan ini, logbook terakhir, dan pengumuman.
- Absensi masuk/pulang dengan selfie dan validasi lokasi.
- Status **hadir** atau **terlambat** dihitung otomatis berdasarkan jam kerja dan toleransi keterlambatan.
- Riwayat absensi.
- Logbook harian: simpan draf, kirim untuk review, dan perbaiki logbook yang dikembalikan admin.
- Satu logbook per tanggal, tidak boleh untuk tanggal yang akan datang, dan harus berada di dalam periode magang.
- Pengajuan izin/sakit dengan lampiran JPG, PNG, atau PDF maksimal 4 MB.
- Melihat status pengajuan izin/sakit dan catatan dari admin.
- Profil: ubah nama, email, dan nomor telepon; lihat data magang; serta ubah password.

### Aturan otomatis dan keamanan

- Login dibatasi maksimal 5 percobaan per menit.
- Akses dibatasi berdasarkan role `admin` dan `mahasiswa`.
- Akun nonaktif ditolak saat login dan diarahkan keluar saat membuka halaman admin/mahasiswa.
- Absensi hanya dapat dilakukan pada hari kerja, di dalam periode magang, dan di dalam radius kantor.
- Jika validasi lokasi aktif, GPS harus terdeteksi dan akurasi GPS harus memenuhi batas yang ditentukan.
- Jika selfie wajib, mahasiswa harus mengunggah foto saat absen.
- Persetujuan izin/sakit otomatis mengisi absensi pada hari kerja dalam rentang pengajuan, kecuali akhir pekan atau hari libur.
- Foto absensi dan lampiran izin disimpan secara privat di disk `local`. Hanya pemilik data atau admin yang dapat mengaksesnya.
- Nilai **alpha** pada laporan dihitung dari hari kerja yang tidak memiliki catatan absensi.

## Teknologi

| Komponen | Teknologi |
| --- | --- |
| Framework | Laravel 13 |
| Bahasa | PHP 8.3+ |
| UI | Livewire 4, Blade, Tailwind CSS 4, Alpine.js 3 |
| Build tool | Vite 8 |
| Database | MariaDB/MySQL; SQLite didukung untuk pengembangan dan pengujian |
| Grafik | Chart.js 4 |
| Export | barryvdh/laravel-dompdf, maatwebsite/excel, phpoffice/phpword |
| Pengolahan gambar | Intervention Image 4 |
| Testing | PHPUnit 12 |
| Format kode | Laravel Pint |

## Kebutuhan Sistem

- PHP 8.3 atau lebih baru dengan ekstensi `gd`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `zip`, `xml`, `dom`, dan `curl`.
- Composer 2.
- Node.js 20.19+ atau 22.12+ dan npm.
- MariaDB/MySQL (disarankan) atau SQLite.
- Browser modern dengan izin kamera dan lokasi.

Untuk penggunaan di server, aplikasi sebaiknya dijalankan melalui HTTPS. Browser umumnya hanya mengizinkan akses lokasi pada HTTPS atau `localhost`.

## Instalasi

### 1. Ambil proyek dan install dependensi

```bash
git clone https://github.com/nandarbq/SIAMAG.git
cd SIAMAG
composer install
npm install
```

### 2. Siapkan file environment

Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

macOS/Linux:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Siapkan database

Buat database bernama `siamag`, lalu sesuaikan konfigurasi berikut di `.env`:

```dotenv
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siamag
DB_USERNAME=root
DB_PASSWORD=
```

Jika ingin memakai SQLite untuk pengembangan lokal:

```dotenv
DB_CONNECTION=sqlite
```

Lalu buat file `database/database.sqlite`.

### 4. Jalankan migrasi dan seeder

```bash
php artisan migrate --seed
```

Perintah tersebut menjalankan `SettingSeeder`, `HolidaySeeder`, dan `UserSeeder`, sehingga aplikasi memiliki pengaturan awal, hari libur, serta satu akun untuk setiap role.

Untuk mengulang database dari awal:

```bash
php artisan migrate:fresh --seed
```

### 5. Build asset frontend

```bash
npm run build
```

### 6. Jalankan aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000`.

## Mode Pengembangan

Satu perintah untuk menjalankan server Laravel, queue listener, dan Vite:

```bash
composer run dev
```

Alternatif dengan terminal terpisah:

```bash
php artisan serve
npm run dev
```

Jika halaman tampil tanpa gaya atau JavaScript, pastikan `npm run dev` sedang berjalan atau jalankan `npm run build`.

## Akun Demo

Seeder membuat dua akun berikut:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@bmkg.go.id` | `password` |
| Mahasiswa | `mahasiswa@bmkg.go.id` | `password` |

Kredensial demo sengaja tidak ditampilkan di halaman login. Gunakan tabel di atas untuk masuk.

Akun di atas dibuat oleh `UserSeeder` dan hanya untuk pengembangan. Sebelum digunakan di lingkungan produksi:

- Ganti password akun tersebut.
- Nonaktifkan akun yang tidak dipakai.

## Pengujian dan Pemeriksaan

```bash
php artisan test
```

Test menggunakan SQLite in-memory dan tidak mengubah database pengembangan. Cakupan test meliputi autentikasi, otorisasi, absensi, logbook, izin, pengaturan, pengumuman, hari libur, laporan/export, akses file privat, serta perhitungan geofence dan progres magang.

Pemeriksaan format kode PHP:

```bash
php vendor/bin/pint --test
```

Build frontend untuk memastikan asset dapat dikompilasi:

```bash
npm run build
```

## Struktur Proyek

```text
app/
  Enums/                       Status absensi, logbook, izin, dan role
  Http/Controllers/
    Admin/ReportExportController.php
    AttendancePhotoController.php
    AuthController.php
    LeaveAttachmentController.php
  Http/Middleware/EnsureRole.php
  Livewire/Admin/              Komponen UI admin
  Livewire/Mahasiswa/          Komponen UI mahasiswa
  Models/                      Model Eloquent
  Notifications/               Notifikasi database
  Services/                    AttendanceService, GeofenceService, ReportService
database/
  factories/                   Factory data uji
  migrations/                  Skema database
  seeders/                     Data awal
resources/
  css/                         Tailwind CSS
  js/                          Alpine.js dan Chart.js
  views/                       Blade dan komponen Livewire
routes/web.php                 Route admin, mahasiswa, dan file privat
tests/                         Unit dan feature test
```

## Catatan Operasional

- Zona waktu aplikasi diatur melalui `APP_TIMEZONE`. Nilai default di `.env.example` adalah `Asia/Jakarta`.
- Jam kerja, radius geofence, batas akurasi GPS, dan toleransi keterlambatan dapat diubah dari menu **Pengaturan** admin.
- Persetujuan izin/sakit tidak menimpa absensi yang sudah memiliki jam masuk.
- Foto selfie dan lampiran izin disimpan di `storage/app/private` dan disajikan melalui route terproteksi. `php artisan storage:link` tidak diperlukan untuk file privat ini.
- Export laporan mengikuti filter bulan, tahun, dan mahasiswa yang dipilih pada halaman laporan.
- Setelah mengubah konfigurasi `.env`, jalankan `php artisan optimize:clear` agar konfigurasi dan cache diperbarui.
- Pada server Linux, pastikan direktori `storage/` dan `bootstrap/cache/` dapat ditulis oleh web server.

## Troubleshooting Singkat

- **Absensi ditolak karena lokasi:** periksa izin lokasi browser, pastikan aplikasi diakses melalui HTTPS atau `localhost`, lalu cocokkan koordinat dan radius kantor di menu **Pengaturan**.
- **Selfie tidak bisa diambil:** pastikan izin kamera diberikan dan browser mendukung akses kamera.
- **Gaya halaman tidak muncul:** jalankan `npm run build`, atau gunakan `composer run dev` saat pengembangan.
- **Data awal tidak muncul:** pastikan migrasi dijalankan dengan `--seed` atau jalankan `php artisan db:seed`.
- **Tidak bisa daftar akun:** memang tidak ada registrasi mandiri. Hubungi admin untuk pembuatan akun.

## Lisensi

Proyek internal BMKG Banyuwangi. Hubungi pemilik repositori untuk informasi penggunaan dan distribusi.
