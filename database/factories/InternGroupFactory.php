<?php

namespace Database\Factories;

use App\Models\InternGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternGroup>
 */
class InternGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'MAG-'.now()->year.'-'.fake()->unique()->numerify('###'),
            'name' => 'Kelompok '.fake()->unique()->numerify('##').' - '.fake()->randomElement([
                'Politeknik Negeri Banyuwangi',
                'Universitas Jember',
                'Universitas Brawijaya',
            ]),
            'institution' => fake()->randomElement([
                'Politeknik Negeri Banyuwangi',
                'Universitas Jember',
                'Universitas Brawijaya',
                'UIN Kiai Haji Achmad Siddiq Jember',
            ]),
            'division' => fake()->optional()->randomElement(['Divisi IT', 'Divisi Meteorologi', 'Divisi Klimatologi']),
            'field_supervisor' => fake()->name(),
            'supervisor_phone' => fake()->numerify('08##########'),
            'start_date' => now()->subMonths(2)->startOfMonth(),
            'end_date' => now()->addMonths(2)->endOfMonth(),
            'is_active' => true,
            'is_individual' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn () => [
            'is_individual' => true,
        ]);
    }
}
