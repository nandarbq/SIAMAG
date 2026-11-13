<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 10))->startOfDay();

        return [
            'user_id' => User::factory()->mahasiswa(),
            'type' => fake()->randomElement([LeaveType::Izin, LeaveType::Sakit]),
            'start_date' => $start,
            'end_date' => $start->copy()->addDays(fake()->numberBetween(0, 2)),
            'reason' => fake()->sentence(12),
            'status' => LeaveStatus::Approved,
        ];
    }

    public function ongoingSick(): static
    {
        return $this->state(fn () => [
            'type' => LeaveType::Sakit,
            'end_date' => null,
        ]);
    }
}
