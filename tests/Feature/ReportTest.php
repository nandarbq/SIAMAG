<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LogbookStatus;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Logbook;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-30 12:00:00', 'Asia/Jakarta'));
    }

    public function test_recap_counts_attendance_logbooks_and_alpha(): void
    {
        $student = $this->makeStudent();

        Holiday::factory()->create(['date' => '2026-11-10']);

        foreach (['2026-11-02', '2026-11-03'] as $date) {
            Attendance::query()->create([
                'user_id' => $student->id,
                'date' => $date,
                'status' => AttendanceStatus::Hadir,
            ]);
        }

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-04',
            'status' => AttendanceStatus::Terlambat,
        ]);

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-05',
            'status' => AttendanceStatus::Izin,
        ]);

        Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-06',
            'status' => AttendanceStatus::Sakit,
        ]);

        Logbook::factory()->approved()->for($student)->create(['date' => '2026-11-02']);
        Logbook::factory()->for($student)->create(['date' => '2026-11-03']);

        $report = app(ReportService::class)->recap(11, 2026);
        $row = $report['rows']->firstWhere('id', $student->id);

        $this->assertSame(2, $row['hadir']);
        $this->assertSame(1, $row['terlambat']);
        $this->assertSame(1, $row['izin']);
        $this->assertSame(1, $row['sakit']);
        $this->assertSame(20, $row['workdays']);
        $this->assertSame(15, $row['alpha']);
        $this->assertSame(1, $row['logbook_approved']);
        $this->assertSame(2, $row['logbook_total']);
        $this->assertSame(15, $row['percentage']);
        $this->assertSame(1, $report['summary']['students']);
    }

    public function test_recap_can_be_filtered_by_student(): void
    {
        $first = $this->makeStudent('First Student');
        $second = $this->makeStudent('Second Student');

        $report = app(ReportService::class)->recap(11, 2026, $second->id);

        $this->assertCount(1, $report['rows']);
        $this->assertSame($second->id, $report['rows']->first()['id']);
        $this->assertNotSame($first->id, $report['rows']->first()['id']);
    }

    private function makeStudent(string $name = 'Student'): User
    {
        $student = User::factory()->mahasiswa()->create(['name' => $name]);
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
