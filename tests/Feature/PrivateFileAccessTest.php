<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateFileAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_owner_cannot_view_attendance_photo(): void
    {
        $student = User::factory()->mahasiswa()->create();
        Storage::disk('local')->put('attendance/in.jpg', 'image-bytes');

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
            'check_in_photo' => 'attendance/in.jpg',
        ]);

        $this->actingAs($student)
            ->get(route('attendance.photo', ['attendance' => $attendance, 'type' => 'in']))
            ->assertForbidden();
    }

    public function test_admin_can_view_student_attendance_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        Storage::disk('local')->put('attendance/out.jpg', 'image-bytes');

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
            'check_out_photo' => 'attendance/out.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('attendance.photo', ['attendance' => $attendance, 'type' => 'out']))
            ->assertOk();
    }

    public function test_other_students_cannot_view_attendance_photo(): void
    {
        $owner = User::factory()->mahasiswa()->create();
        $other = User::factory()->mahasiswa()->create();
        Storage::disk('local')->put('attendance/in.jpg', 'image-bytes');

        $attendance = Attendance::query()->create([
            'user_id' => $owner->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
            'check_in_photo' => 'attendance/in.jpg',
        ]);

        $this->actingAs($other)
            ->get(route('attendance.photo', ['attendance' => $attendance, 'type' => 'in']))
            ->assertForbidden();
    }

    public function test_attendance_photo_returns_not_found_for_invalid_type_or_missing_file(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        $attendance = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Hadir,
            'check_in_photo' => 'attendance/missing.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('attendance.photo', ['attendance' => $attendance, 'type' => 'side']))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('attendance.photo', ['attendance' => $attendance, 'type' => 'in']))
            ->assertNotFound();
    }

    public function test_owner_and_admin_can_view_leave_attachment(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        Storage::disk('local')->put('leaves/surat.pdf', 'pdf-bytes');

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
            'attachment_path' => 'leaves/surat.pdf',
        ]);

        $this->actingAs($student)
            ->get(route('leave.attachment', $leave))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('leave.attachment', $leave))
            ->assertOk();
    }

    public function test_other_students_cannot_view_leave_attachment(): void
    {
        $owner = User::factory()->mahasiswa()->create();
        $other = User::factory()->mahasiswa()->create();
        Storage::disk('local')->put('leaves/surat.pdf', 'pdf-bytes');

        $leave = LeaveRequest::query()->create([
            'user_id' => $owner->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Approved,
            'attachment_path' => 'leaves/surat.pdf',
        ]);

        $this->actingAs($other)
            ->get(route('leave.attachment', $leave))
            ->assertForbidden();
    }
}
