<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Livewire\Admin\Attendances\Index as AdminAttendanceIndex;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
    }

    public function test_admin_can_correct_attendance_record(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
            'check_in_at' => Carbon::parse('2026-11-09 07:30:00'),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminAttendanceIndex::class)
            ->call('openCorrect', $attendance->id)
            ->assertSet('editStatus', AttendanceStatus::Hadir->value)
            ->set('editStatus', AttendanceStatus::Izin->value)
            ->set('editNote', 'Koreksi oleh admin.')
            ->set('editCheckIn', '08:00')
            ->set('editCheckOut', '15:00')
            ->call('saveCorrect')
            ->assertHasNoErrors();

        $attendance->refresh();

        $this->assertSame(AttendanceStatus::Izin, $attendance->status);
        $this->assertSame('Koreksi oleh admin.', $attendance->note);
        $this->assertSame('08:00', $attendance->check_in_at->format('H:i'));
        $this->assertSame('15:00', $attendance->check_out_at->format('H:i'));
    }

    public function test_admin_correction_recalculates_late_minutes(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Terlambat,
            'late_minutes' => 90,
            'check_in_at' => Carbon::parse('2026-11-09 07:30:00'),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminAttendanceIndex::class)
            ->call('openCorrect', $attendance->id)
            ->set('editCheckIn', '08:00')
            ->call('saveCorrect')
            ->assertHasNoErrors();

        $this->assertSame(15, $attendance->fresh()->late_minutes);
    }

    public function test_admin_can_clear_attendance_times_and_late_minutes(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Terlambat,
            'late_minutes' => 30,
            'check_in_at' => Carbon::parse('2026-11-09 08:00:00'),
            'check_out_at' => Carbon::parse('2026-11-09 16:00:00'),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminAttendanceIndex::class)
            ->call('openCorrect', $attendance->id)
            ->set('editStatus', AttendanceStatus::Hadir->value)
            ->set('editCheckIn', '')
            ->set('editCheckOut', '')
            ->call('saveCorrect')
            ->assertHasNoErrors();

        $attendance->refresh();

        $this->assertSame(AttendanceStatus::Hadir, $attendance->status);
        $this->assertNull($attendance->check_in_at);
        $this->assertNull($attendance->check_out_at);
        $this->assertSame(0, $attendance->late_minutes);
    }

    public function test_admin_correction_rejects_invalid_time_format(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminAttendanceIndex::class)
            ->call('openCorrect', $attendance->id)
            ->set('editCheckIn', '25:99')
            ->call('saveCorrect')
            ->assertHasErrors(['editCheckIn']);
    }

    public function test_admin_can_filter_attendance_by_date(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
        ]);

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-08',
            'status' => AttendanceStatus::Hadir,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminAttendanceIndex::class)
            ->set('date', '2026-11-09')
            ->assertViewHas('attendances', function ($attendances) {
                return $attendances->total() === 1
                    && $attendances->first()->date->toDateString() === '2026-11-09';
            });
    }
}
