<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
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

    public function test_dashboard_shows_today_leave_and_ongoing_sick_monitoring(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create(['is_active' => true]);

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Sakit,
            'note' => 'Pengajuan Sakit tercatat.',
        ]);

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-09',
            'end_date' => null,
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertViewHas('permit', 1)
            ->assertViewHas('ongoingSick', 1)
            ->assertSee('Sakit Berlangsung');
    }
}
