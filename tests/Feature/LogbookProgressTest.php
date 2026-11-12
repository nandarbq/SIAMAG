<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Livewire\Mahasiswa\Dashboard as StudentDashboard;
use App\Livewire\Mahasiswa\Logbooks\Index as StudentLogbookIndex;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Logbook;
use App\Models\User;
use App\Services\LogbookProgressService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogbookProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_counts_only_past_attended_workdays(): void
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

        $this->assertSame(3, $progress['expected']);
        $this->assertSame(2, $progress['filled']);
        $this->assertSame(1, $progress['missing']);
        $this->assertSame(67, $progress['percentage']);
        $this->assertSame(['2026-11-03'], $progress['missing_dates']);
        $this->assertSame(2, $progress['total']);
        $this->assertSame('missing', $progress['today_status']);
    }

    public function test_today_is_reported_separately_from_missing_days(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-10', AttendanceStatus::Hadir);
        Logbook::factory()->for($student)->create(['date' => '2026-11-10']);

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame(0, $progress['expected']);
        $this->assertNull($progress['percentage']);
        $this->assertSame([], $progress['missing_dates']);
        $this->assertSame('filled', $progress['today_status']);
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
        $this->assertNull($progress['percentage']);
    }

    public function test_progress_reports_not_attended_today(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame('not_attended', $progress['today_status']);
    }

    public function test_progress_is_empty_without_internship_profile(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame('no_profile', $progress['today_status']);
        $this->assertSame(0, $progress['expected']);
        $this->assertNull($progress['percentage']);
    }

    public function test_progress_before_internship_starts(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame('not_started', $progress['today_status']);
        $this->assertSame(0, $progress['expected']);
    }

    public function test_progress_after_internship_ends_includes_all_attended_days(): void
    {
        $this->travelTo(Carbon::parse('2026-12-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-02', AttendanceStatus::Hadir);
        $this->attendance($student, '2026-11-03', AttendanceStatus::Hadir);
        Logbook::factory()->for($student)->create(['date' => '2026-11-02']);

        $progress = app(LogbookProgressService::class)->for($student);

        $this->assertSame(2, $progress['expected']);
        $this->assertSame(1, $progress['filled']);
        $this->assertSame(['2026-11-03'], $progress['missing_dates']);
        $this->assertSame('outside', $progress['today_status']);
    }

    public function test_logbook_page_renders_progress_and_missing_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-03', AttendanceStatus::Hadir);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->assertSee('Ada 1 hari kerja yang belum diisi logbook.')
            ->assertSee('03 Nov')
            ->assertSee('Belum absen');
    }

    public function test_dashboard_renders_logbook_progress_and_alert(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();
        $this->attendance($student, '2026-11-03', AttendanceStatus::Hadir);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Progres Logbook')
            ->assertSee('Ada 1 hari kerja yang belum diisi logbook.');
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
