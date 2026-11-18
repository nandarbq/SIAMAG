<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\InternProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $configuredPassword = env('SEED_DEFAULT_PASSWORD');

        if (app()->isProduction() && blank($configuredPassword)) {
            $this->command?->warn('UserSeeder dilewati: set SEED_DEFAULT_PASSWORD untuk membuat akun awal di production.');

            return;
        }

        $password = $configuredPassword ?: 'password';

        $admin = User::updateOrCreate(
            ['email' => 'admin@bmkg.go.id'],
            [
                'name' => 'Administrator BMKG',
                'password' => $password,
                'role' => UserRole::Admin,
                'phone' => '081234567890',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $student = User::updateOrCreate(
            ['email' => 'mahasiswa@bmkg.go.id'],
            [
                'name' => 'Nandar Assabiqi',
                'password' => $password,
                'role' => UserRole::Mahasiswa,
                'nim' => '20241001',
                'phone' => '081298765432',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        InternProfile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'institution' => 'Universitas Jember',
                'major' => 'Teknologi Informasi',
                'semester' => '6',
                'start_date' => now()->subMonths(2)->startOfMonth(),
                'end_date' => now()->addMonths(2)->endOfMonth(),
                'field_supervisor' => 'Budi Santoso',
                'supervisor_phone' => '081211112222',
            ]
        );

        $this->command?->info('Admin: admin@bmkg.go.id');
        $this->command?->info('Mahasiswa: mahasiswa@bmkg.go.id');
        $this->command?->info('Password akun awal mengikuti SEED_DEFAULT_PASSWORD (default lokal: password).');
    }
}
