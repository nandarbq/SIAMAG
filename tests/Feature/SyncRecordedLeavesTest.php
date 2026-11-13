<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncRecordedLeavesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_records_attendance_for_recorded_leaves(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Approved,
        ]);

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-10',
            'end_date' => null,
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
        ]);

        $this->artisan('attendance:sync-recorded-leaves')
            ->assertSuccessful();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'date' => '2026-11-09 00:00:00',
            'status' => AttendanceStatus::Izin->value,
        ]);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'date' => '2026-11-10 00:00:00',
            'status' => AttendanceStatus::Sakit->value,
        ]);
        $this->assertSame(2, Attendance::query()->where('user_id', $student->id)->count());
    }

    public function test_command_ignores_rejected_leave(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Rejected,
        ]);

        $this->artisan('attendance:sync-recorded-leaves')
            ->assertSuccessful();

        $this->assertSame(0, Attendance::query()->where('user_id', $student->id)->count());
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
