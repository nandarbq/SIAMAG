<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\Attendance\Index as StudentAttendanceIndex;
use App\Livewire\Mahasiswa\Dashboard as StudentDashboard;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\User;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkdayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
    }

    public function test_workdays_default_to_monday_through_friday(): void
    {
        $workdays = app(WorkdayService::class);

        $this->assertSame([1, 2, 3, 4, 5], $workdays->workdays());
        $this->assertTrue($workdays->isWorkday(Carbon::parse('2026-11-09')));
        $this->assertFalse($workdays->isWorkday(Carbon::parse('2026-11-07')));
    }

    public function test_configured_workdays_are_used(): void
    {
        Setting::put('workdays', '1,2,3,4,5,6');

        $workdays = app(WorkdayService::class);

        $this->assertSame([1, 2, 3, 4, 5, 6], $workdays->workdays());
        $this->assertTrue($workdays->isWorkday(Carbon::parse('2026-11-07')));
        $this->assertFalse($workdays->isWorkday(Carbon::parse('2026-11-08')));
    }

    public function test_holidays_are_not_workdays_even_when_configured(): void
    {
        Holiday::factory()->create([
            'date' => '2026-11-09',
            'name' => 'Hari Libur Khusus',
        ]);

        $workdays = app(WorkdayService::class);

        $this->assertFalse($workdays->isWorkday(Carbon::parse('2026-11-09')));
        $this->assertSame('Hari Libur Khusus', $workdays->nonWorkingLabel(Carbon::parse('2026-11-09')));
    }

    public function test_non_working_label_describes_weekends_and_other_off_days(): void
    {
        $workdays = app(WorkdayService::class);

        $this->assertSame('Akhir pekan', $workdays->nonWorkingLabel(Carbon::parse('2026-11-07')));

        Setting::put('workdays', '1,2,4,5');

        $this->assertSame('Bukan hari kerja', $workdays->nonWorkingLabel(Carbon::parse('2026-11-11')));
    }

    public function test_student_dashboard_and_attendance_show_holiday_notice(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        Holiday::factory()->create([
            'date' => '2026-11-09',
            'name' => 'Hari Libur Khusus',
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertViewHas('isWorkday', false)
            ->assertViewHas('todayHoliday', fn (?Holiday $holiday) => $holiday?->name === 'Hari Libur Khusus')
            ->assertSee('Libur: Hari Libur Khusus');

        Livewire::actingAs($student)
            ->test(StudentAttendanceIndex::class)
            ->assertViewHas('isWorkday', false)
            ->assertSee('Hari ini libur')
            ->assertSee('Hari Libur Khusus')
            ->assertDontSee('Kamera belum aktif');
    }
}
