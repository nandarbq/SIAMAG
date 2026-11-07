<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'office_name' => 'BMKG Banyuwangi',
            'office_address' => 'Jl. Raya Jember, Banyuwangi, Jawa Timur',
            'office_lat' => '-8.2192000',
            'office_lng' => '114.3692000',
            'radius_m' => '150',
            'max_accuracy_m' => '100',
            'work_start' => '07:30',
            'work_end' => '16:00',
            'late_tolerance_min' => '15',
            'require_selfie' => '1',
            'require_geofence' => '1',
        ];

        foreach ($settings as $key => $value) {
            Setting::put($key, $value);
        }
    }
}
