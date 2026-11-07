<?php

namespace Database\Factories;

use App\Models\InternProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternProfile>
 */
class InternProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mahasiswa(),
            'institution' => fake()->randomElement([
                'Universitas Jember',
                'Politeknik Negeri Banyuwangi',
                'Universitas Brawijaya',
                'UIN Kiai Haji Achmad Siddiq Jember',
            ]),
            'major' => fake()->randomElement([
                'Teknik Informatika',
                'Sistem Informasi',
                'Ilmu Komputer',
                'Teknologi Informasi',
            ]),
            'semester' => fake()->numberBetween(4, 8),
            'start_date' => now()->subMonths(2)->startOfMonth(),
            'end_date' => now()->addMonths(2)->endOfMonth(),
            'field_supervisor' => fake()->name(),
            'supervisor_phone' => fake()->numerify('08##########'),
        ];
    }
}
