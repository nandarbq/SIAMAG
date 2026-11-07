<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $checkIn = now()->setTime(7, fake()->numberBetween(15, 59));
        $checkOut = now()->setTime(16, fake()->numberBetween(0, 30));

        return [
            'user_id' => User::factory()->mahasiswa(),
            'date' => now()->toDateString(),
            'check_in_at' => $checkIn,
            'check_out_at' => $checkOut,
            'status' => AttendanceStatus::Hadir,
            'late_minutes' => 0,
            'check_in_lat' => -8.2192000,
            'check_in_lng' => 114.3692000,
            'check_out_lat' => -8.2192000,
            'check_out_lng' => 114.3692000,
            'check_in_distance' => fake()->numberBetween(5, 80),
            'check_out_distance' => fake()->numberBetween(5, 80),
            'check_in_accuracy' => fake()->numberBetween(5, 30),
            'check_out_accuracy' => fake()->numberBetween(5, 30),
            'check_in_ip' => '127.0.0.1',
            'check_out_ip' => '127.0.0.1',
        ];
    }

    public function late(): static
    {
        return $this->state(fn () => [
            'check_in_at' => now()->setTime(8, fake()->numberBetween(1, 45)),
            'status' => AttendanceStatus::Terlambat,
            'late_minutes' => fake()->numberBetween(1, 60),
        ]);
    }

    public function leave(AttendanceStatus $status = AttendanceStatus::Izin): static
    {
        return $this->state(fn () => [
            'check_in_at' => null,
            'check_out_at' => null,
            'status' => $status,
            'late_minutes' => 0,
            'check_in_photo' => null,
            'check_out_photo' => null,
            'check_in_lat' => null,
            'check_in_lng' => null,
            'check_out_lat' => null,
            'check_out_lng' => null,
        ]);
    }
}
