<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Livewire\Mahasiswa\Dashboard as StudentDashboard;
use App\Livewire\Mahasiswa\Logbooks\Index as StudentLogbookIndex;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Logbook;
use App\Models\User;
use App\Services\InternshipWeekService;
use App\Services\LogbookProgressService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogbookProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_counts_only_weeks_with_past_attendance(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        Holiday::factory()->create(['date' => '2026-11-09']);

        $this->attendance($student, '2026-11-02', AttendanceStatus::Hadir);
        $this->attendance($student, '2026-11-03', AttendanceStatus::Terlambat);
        $this->attendance($student, '2026-11-04', AttendanceStatus::Izin);
        $this->attendance($student, '2026-11-05', AttendanceStatus::Sakit);
        $this->attendance($student, '2026-11-06', AttendanceStatus::Hadir);
        $this->attendance($student, '2026-11-10', AttendanceStatus::Hadir);

        Logbook::factory()->for($student)->create(['date' => '2026-11-02']);
        Logbook::factory()->for($student)->create(['date' => '2026-11-06']);

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame(2, $progress['expected']);
        $this->assertSame(1, $progress['filled']);
        $this->assertSame(1, $progress['missing']);
        $this->assertSame(50, $progress['percentage']);
        $this->assertCount(1, $progress['missing_weeks']);
        $this->assertSame(2, $progress['missing_weeks'][0]['number']);
        $this->assertSame('Pekan 2 · 09 Nov–15 Nov 2026', $progress['missing_weeks'][0]['label']);
        $this->assertSame(2, $progress['total']);
        $this->assertSame('missing', $progress['week_status']);
    }

    public function test_current_week_is_reported_separately_from_missing_weeks(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-10', AttendanceStatus::Hadir);
        Logbook::factory()->for($student)->create(['date' => '2026-11-10']);

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame(1, $progress['expected']);
        $this->assertSame(100, $progress['percentage']);
        $this->assertSame([], $progress['missing_weeks']);
        $this->assertSame('filled', $progress['week_status']);
    }

    public function test_progress_excludes_leave_sick_holidays_and_weekends(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        Holiday::factory()->create(['date' => '2026-11-09']);

        $this->attendance($student, '2026-11-04', AttendanceStatus::Izin);
        $this->attendance($student, '2026-11-05', AttendanceStatus::Sakit);
        $this->attendance($student, '2026-11-06', AttendanceStatus::Libur);

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame(0, $progress['expected']);
        $this->assertSame(0, $progress['missing']);
        $this->assertSame([], $progress['missing_weeks']);
        $this->assertNull($progress['percentage']);
        $this->assertSame('not_required', $progress['week_status']);
    }

    public function test_progress_reports_current_week_without_attendance(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame('not_required', $progress['week_status']);
    }

    public function test_progress_is_empty_without_internship_profile(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame('no_profile', $progress['week_status']);
        $this->assertSame(0, $progress['expected']);
        $this->assertNull($progress['percentage']);
    }

    public function test_progress_before_internship_starts(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame('not_started', $progress['week_status']);
        $this->assertSame(0, $progress['expected']);
    }

    public function test_progress_after_internship_ends_includes_all_attended_weeks(): void
    {
        $this->travelTo(Carbon::parse('2026-12-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-02', AttendanceStatus::Hadir);
        $this->attendance($student, '2026-11-09', AttendanceStatus::Hadir);
        Logbook::factory()->for($student)->create(['date' => '2026-11-02']);

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame(2, $progress['expected']);
        $this->assertSame(1, $progress['filled']);
        $this->assertCount(1, $progress['missing_weeks']);
        $this->assertSame(2, $progress['missing_weeks'][0]['number']);
        $this->assertSame('outside', $progress['week_status']);
    }

    public function test_weeks_are_anchored_to_the_internship_start_date(): void
    {
        $student = $this->makeStudent();
        $profile = $student->internProfile;
        $weeks = app(InternshipWeekService::class);

        $this->assertSame(5, $weeks->count($profile));
        $this->assertSame(1, $weeks->number($profile, Carbon::parse('2026-11-08')));
        $this->assertSame(2, $weeks->number($profile, Carbon::parse('2026-11-09')));
        $this->assertSame('Pekan 1 · 02 Nov–08 Nov 2026', $weeks->labelForWeek($profile, 1));
        $this->assertSame('Pekan 5 · 30 Nov–30 Nov 2026', $weeks->labelForWeek($profile, 5));
    }

    public function test_logbook_page_renders_weekly_progress_and_missing_weeks(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-03', AttendanceStatus::Hadir);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->assertSee('Ada 1 pekan yang belum diisi logbook.')
            ->assertSee('Pekan 1 · 02 Nov–08 Nov 2026')
            ->assertSee('Belum perlu');
    }

    public function test_dashboard_renders_weekly_logbook_progress_and_alert(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-03', AttendanceStatus::Hadir);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Progres Logbook')
            ->assertSee('Ada 1 pekan yang belum diisi logbook.');
    }

    private function makeStudent(): User
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => Carbon::parse('2026-11-02'),
            'end_date' => Carbon::parse('2026-11-30'),
        ]);

        return $student->load('internProfile');
    }

    private function attendance(User $student, string $date, AttendanceStatus $status): void
    {
        Attendance::factory()->for($student)->create([
            'date' => $date,
            'status' => $status,
        ]);
    }
}
