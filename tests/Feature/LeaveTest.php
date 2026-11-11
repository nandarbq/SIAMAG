<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Livewire\Admin\Leaves\Index as AdminLeaveIndex;
use App\Livewire\Mahasiswa\Leaves\Index as StudentLeaveIndex;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
    }

    public function test_student_can_submit_a_leave_request(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLeaveIndex::class)
            ->call('openCreate')
            ->set('type', LeaveType::Izin->value)
            ->set('startDate', '2026-11-10')
            ->set('endDate', '2026-11-10')
            ->set('reason', 'Mengurus keperluan keluarga yang mendesak.')
            ->call('submit')
            ->assertHasNoErrors();

        $leave = LeaveRequest::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame(LeaveType::Izin, $leave->type);
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertSame('2026-11-10', $leave->start_date->toDateString());
    }

    public function test_student_can_open_leave_form_on_final_internship_day(): void
    {
        $this->travelTo(Carbon::parse('2026-12-31 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLeaveIndex::class)
            ->call('openCreate')
            ->assertSet('showModal', true);
    }

    public function test_overlapping_leave_requests_are_rejected(): void
    {
        $student = $this->makeStudent();

        LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Pending,
        ]);

        Livewire::actingAs($student)
            ->test(StudentLeaveIndex::class)
            ->set('type', LeaveType::Sakit->value)
            ->set('startDate', '2026-11-11')
            ->set('endDate', '2026-11-12')
            ->set('reason', 'Demam dan perlu istirahat di rumah.')
            ->call('submit')
            ->assertHasErrors(['startDate']);
    }

    public function test_student_can_delete_a_pending_request(): void
    {
        $student = $this->makeStudent();

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Pending,
        ]);

        Livewire::actingAs($student)
            ->test(StudentLeaveIndex::class)
            ->call('delete', $leave->id);

        $this->assertDatabaseMissing('leave_requests', ['id' => $leave->id]);
    }

    public function test_admin_approval_marks_attendance_days_as_leave(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Sakit,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'reason' => 'Demam dan perlu istirahat di rumah.',
            'status' => LeaveStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLeaveIndex::class)
            ->call('openReview', $leave->id)
            ->set('reviewNote', 'Disetujui, semoga lekas sembuh.')
            ->call('approve', $leave->id)
            ->assertHasNoErrors();

        $leave->refresh();

        $this->assertSame(LeaveStatus::Approved, $leave->status);
        $this->assertSame($admin->id, $leave->reviewed_by);

        foreach (['2026-11-10', '2026-11-11'] as $date) {
            $attendance = Attendance::query()
                ->where('user_id', $student->id)
                ->whereDate('date', $date)
                ->firstOrFail();

            $this->assertSame(AttendanceStatus::Sakit, $attendance->status);
        }
    }

    public function test_admin_must_add_a_note_when_rejecting_a_leave_request(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLeaveIndex::class)
            ->call('openReview', $leave->id)
            ->set('reviewNote', '')
            ->call('reject', $leave->id)
            ->assertHasErrors(['reviewNote']);

        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
    }

    public function test_admin_can_reject_a_leave_request_with_a_note(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLeaveIndex::class)
            ->call('openReview', $leave->id)
            ->set('reviewNote', 'Lampiran surat belum lengkap.')
            ->call('reject', $leave->id)
            ->assertHasNoErrors();

        $leave->refresh();

        $this->assertSame(LeaveStatus::Rejected, $leave->status);
        $this->assertSame('Lampiran surat belum lengkap.', $leave->review_note);
        $this->assertSame($admin->id, $leave->reviewed_by);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_student_cannot_delete_a_processed_leave_request(): void
    {
        $student = $this->makeStudent();

        $leave = LeaveRequest::query()->create([
            'user_id' => $student->id,
            'type' => LeaveType::Izin,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Kegiatan keluarga di luar kota.',
            'status' => LeaveStatus::Approved,
        ]);

        Livewire::actingAs($student)
            ->test(StudentLeaveIndex::class)
            ->call('delete', $leave->id);

        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id]);
    }

    public function test_student_cannot_request_leave_outside_the_internship_period(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLeaveIndex::class)
            ->set('type', LeaveType::Izin->value)
            ->set('startDate', '2027-01-01')
            ->set('endDate', '2027-01-02')
            ->set('reason', 'Kegiatan keluarga di luar kota.')
            ->call('submit')
            ->assertHasErrors(['startDate']);
    }

    public function test_student_cannot_check_in_on_an_approved_leave_day(): void
    {
        $student = $this->makeStudent();

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-09',
            'status' => AttendanceStatus::Izin,
            'note' => 'Pengajuan izin disetujui.',
        ]);

        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->checkIn($student, [
            'lat' => -8.2192,
            'lng' => 114.3692,
            'accuracy' => 10,
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
