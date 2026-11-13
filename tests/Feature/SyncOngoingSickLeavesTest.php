<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncOngoingSickLeavesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_records_attendance_for_ongoing_sick_leave(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 00:05:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-09',
            'end_date' => null,
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
        ]);

        $this->artisan('attendance:sync-ongoing-sick-leaves')
            ->assertSuccessful();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'date' => '2026-11-09 00:00:00',
            'status' => AttendanceStatus::Sakit->value,
        ]);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'date' => '2026-11-10 00:00:00',
            'status' => AttendanceStatus::Sakit->value,
        ]);
        $this->assertSame(2, Attendance::query()->where('user_id', $student->id)->count());
    }

    public function test_command_ignores_closed_sick_leave(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 00:05:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
        ]);

        $this->artisan('attendance:sync-ongoing-sick-leaves')
            ->assertSuccessful();

        $this->assertSame(0, Attendance::query()->where('user_id', $student->id)->count());
    }

    public function test_command_does_not_recreate_attendance_after_recovery(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 00:05:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-09',
            'end_date' => null,
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
        ]);

        app(AttendanceService::class)->closeSickLeave($leave, Carbon::parse('2026-11-11'));

        $this->artisan('attendance:sync-ongoing-sick-leaves')
            ->assertSuccessful();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'date' => '2026-11-10 00:00:00',
            'status' => AttendanceStatus::Sakit->value,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id,
            'date' => '2026-11-11 00:00:00',
        ]);
    }

    private function makeStudent(): User
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        return $student;
    }
}
