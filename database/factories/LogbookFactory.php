<?php

namespace Database\Factories;

use App\Enums\LogbookStatus;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logbook>
 */
class LogbookFactory extends Factory
{
    public function definition(): array
    {
        $activities = [
            'Mempelajari alur kerja Stasiun Meteorologi',
            'Membantu pengolahan data cuaca harian',
            'Menginput data observasi ke sistem',
            'Menyusun dokumentasi kegiatan magang',
            'Mengikuti briefing operasional pagi',
            'Membantu pemeriksaan peralatan observasi',
        ];

        return [
            'user_id' => User::factory()->mahasiswa(),
            'date' => now()->toDateString(),
            'activity' => fake()->randomElement($activities),
            'description' => fake()->paragraph(3),
            'start_time' => '08:00',
            'end_time' => '15:30',
            'result' => fake()->sentence(10),
            'obstacle' => fake()->boolean(30) ? fake()->sentence(6) : null,
            'status' => LogbookStatus::Draft,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => LogbookStatus::Submitted]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => LogbookStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => LogbookStatus::Rejected,
            'review_note' => 'Mohon lengkapi uraian hasil kegiatan.',
            'reviewed_at' => now(),
        ]);
    }
}
