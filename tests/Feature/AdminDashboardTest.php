<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_count_missing_attendance_on_weekends(): void
    {
        $this->travelTo(Carbon::parse('2026-11-07 10:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->admin()->create();
        User::factory()->mahasiswa()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertViewHas('alpha', 0)
            ->assertViewHas('chart', fn (array $chart) => end($chart['alpha']) === 0);
    }

    public function test_dashboard_does_not_count_missing_attendance_on_holidays(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->admin()->create();
        User::factory()->mahasiswa()->create(['is_active' => true]);
        Holiday::factory()->create(['date' => '2026-11-09']);

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertViewHas('alpha', 0)
            ->assertViewHas('chart', fn (array $chart) => end($chart['alpha']) === 0);
    }
}
