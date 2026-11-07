<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Tahun Baru 2026'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila'],
            ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan Republik Indonesia'],
            ['date' => '2026-12-25', 'name' => 'Hari Raya Natal'],
            ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(['date' => $holiday['date']], ['name' => $holiday['name']]);
        }
    }
}
