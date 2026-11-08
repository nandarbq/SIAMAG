<?php

namespace Tests\Unit;

use App\Models\InternProfile;
use Carbon\Carbon;
use Tests\TestCase;

class InternProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-06 12:00:00', 'Asia/Jakarta'));
    }

    public function test_progress_is_zero_without_valid_dates(): void
    {
        $this->assertSame(0, (new InternProfile)->progressPercent());

        $profile = new InternProfile([
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
        ]);

        $this->assertSame(0, $profile->progressPercent());
    }

    public function test_progress_is_zero_before_internship_starts(): void
    {
        $profile = new InternProfile([
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
        ]);

        $this->assertSame(0, $profile->progressPercent());
    }

    public function test_progress_is_calculated_at_midpoint(): void
    {
        $profile = new InternProfile([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-11',
        ]);

        $this->assertSame(50, $profile->progressPercent());
    }

    public function test_progress_is_capped_at_one_hundred_after_internship_ends(): void
    {
        $profile = new InternProfile([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $this->assertSame(100, $profile->progressPercent());
    }

    public function test_remaining_days_are_calculated_and_never_negative(): void
    {
        $profile = new InternProfile([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-11',
        ]);

        $this->assertSame(5, $profile->remainingDays());

        $finished = new InternProfile([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $this->assertSame(0, $finished->remainingDays());
        $this->assertSame(0, (new InternProfile)->remainingDays());
    }
}
