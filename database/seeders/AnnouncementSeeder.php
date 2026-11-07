<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@bmkg.go.id')->first();

        $announcements = [
            [
                'title' => 'Selamat Datang di SIAMAG',
                'body' => 'SIAMAG adalah sistem informasi absensi dan logbook magang BMKG Banyuwangi. Silakan lengkapi profil dan mulai isi logbook harian Anda.',
            ],
            [
                'title' => 'Pengingat Pengisian Logbook',
                'body' => 'Pastikan logbook harian diisi sebelum pukul 16.00 WIB. Logbook yang sudah disubmit akan direview oleh admin.',
            ],
            [
                'title' => 'Perubahan Jam Operasional',
                'body' => 'Mulai pekan depan, jam kerja mengikuti jadwal operasional Stasiun Meteorologi Banyuwangi. Silakan cek menu pengaturan untuk detailnya.',
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::updateOrCreate(
                ['title' => $announcement['title']],
                [
                    'body' => $announcement['body'],
                    'is_active' => true,
                    'published_at' => now(),
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
