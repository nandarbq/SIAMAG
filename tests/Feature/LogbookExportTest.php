<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\LogbookExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LogbookExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_export_opens_seven_days_before_internship_ends_by_default(): void
    {
        $student = $this->makeStudent('2026-11-01', '2026-12-31');
        $service = app(LogbookExportService::class);

        $this->travelTo(Carbon::parse('2026-12-23 23:59:00', 'Asia/Jakarta'));
        $this->assertFalse($service->canExport($student->internProfile));

        $this->travelTo(Carbon::parse('2026-12-24 00:00:00', 'Asia/Jakarta'));
        $this->assertTrue($service->canExport($student->internProfile));
        $this->assertSame('2026-12-24', $service->opensAt($student->internProfile)->toDateString());
    }

    public function test_export_remains_available_after_internship_ends(): void
    {
        $student = $this->makeStudent('2026-11-01', '2026-12-31');

        $this->travelTo(Carbon::parse('2027-01-15 10:00:00', 'Asia/Jakarta'));

        $this->assertTrue(app(LogbookExportService::class)->canExport($student->internProfile));
    }

    public function test_export_opens_on_start_date_when_internship_is_shorter_than_lead_days(): void
    {
        Setting::put('logbook_export_lead_days', '7');

        $student = $this->makeStudent('2026-12-30', '2026-12-31');
        $service = app(LogbookExportService::class);

        $this->assertSame('2026-12-30', $service->opensAt($student->internProfile)->toDateString());
    }

    public function test_lead_days_setting_is_clamped_between_one_and_thirty(): void
    {
        $service = app(LogbookExportService::class);

        Setting::put('logbook_export_lead_days', '0');
        $this->assertSame(1, $service->leadDays());

        Setting::put('logbook_export_lead_days', '99');
        $this->assertSame(30, $service->leadDays());
    }

    private function makeStudent(string $startDate, string $endDate): User
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => Carbon::parse($startDate),
            'end_date' => Carbon::parse($endDate),
        ]);

        return $student->load('internProfile');
    }
}
