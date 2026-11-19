<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\Logbooks\Index as StudentLogbookIndex;
use App\Models\Logbook;
use App\Models\Setting;
use App\Models\User;
use App\Services\LogbookExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_export_rows_are_sorted_with_week_labels_and_thumbnails(): void
    {
        Storage::fake('local');

        $student = $this->makeStudent('2026-11-02', '2026-11-30');
        Logbook::factory()->for($student)->create([
            'date' => '2026-11-10',
            'activity' => 'Kegiatan kedua',
        ]);
        $earlier = Logbook::factory()->for($student)->create([
            'date' => '2026-11-03',
            'activity' => 'Kegiatan pertama',
        ]);

        $path = 'logbook-photos/'.$student->id.'/foto.jpg';
        Storage::disk('local')->put($path, UploadedFile::fake()->image('foto.jpg', 640, 480)->getContent());
        $earlier->update(['attachment_path' => $path]);

        $rows = app(LogbookExportService::class)->rowsFor($student);

        $this->assertSame([1, 2], $rows->pluck('number')->all());
        $this->assertSame(['Kegiatan pertama', 'Kegiatan kedua'], $rows->pluck('activity')->all());
        $this->assertSame('Pekan 1 · 02 Nov–08 Nov 2026', $rows[0]['week']);
        $this->assertSame('Pekan 2 · 09 Nov–15 Nov 2026', $rows[1]['week']);
        $this->assertNotNull($rows[0]['photo_binary']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $rows[0]['photo_data_uri']);
        $this->assertNull($rows[1]['photo_binary']);
        $this->assertNull($rows[1]['photo_data_uri']);
    }

    public function test_export_rows_use_fallback_when_photo_is_missing_or_unreadable(): void
    {
        Storage::fake('local');

        $student = $this->makeStudent('2026-11-02', '2026-11-30');
        Logbook::factory()->for($student)->create([
            'date' => '2026-11-03',
            'attachment_path' => 'missing.jpg',
        ]);
        Logbook::factory()->for($student)->create([
            'date' => '2026-11-04',
            'attachment_path' => 'broken.jpg',
        ]);
        Storage::disk('local')->put('broken.jpg', 'not-an-image');

        $rows = app(LogbookExportService::class)->rowsFor($student);

        $this->assertNull($rows[0]['photo_binary']);
        $this->assertNull($rows[0]['photo_data_uri']);
        $this->assertNull($rows[1]['photo_binary']);
        $this->assertNull($rows[1]['photo_data_uri']);
    }

    public function test_student_can_download_logbook_as_word_when_window_is_open(): void
    {
        $student = $this->makeStudent('2026-11-01', '2026-12-31');
        Logbook::factory()->for($student)->create([
            'date' => '2026-11-03',
            'activity' => 'Analisis data curah hujan',
        ]);

        $this->travelTo(Carbon::parse('2026-12-24 10:00:00', 'Asia/Jakarta'));

        $this->actingAs($student)
            ->get(route('mahasiswa.logbooks.word'))
            ->assertOk()
            ->assertDownload('logbook-'.$student->nim.'.docx');
    }

    public function test_student_can_download_logbook_as_pdf_when_window_is_open(): void
    {
        $student = $this->makeStudent('2026-11-01', '2026-12-31');
        Logbook::factory()->for($student)->create([
            'date' => '2026-11-03',
            'activity' => 'Analisis data curah hujan',
        ]);

        $this->travelTo(Carbon::parse('2026-12-24 10:00:00', 'Asia/Jakarta'));

        $this->actingAs($student)
            ->get(route('mahasiswa.logbooks.pdf'))
            ->assertOk()
            ->assertDownload('logbook-'.$student->nim.'.pdf');
    }

    public function test_student_cannot_download_logbook_before_window_opens(): void
    {
        $student = $this->makeStudent('2026-11-01', '2026-12-31');

        $this->travelTo(Carbon::parse('2026-12-23 23:59:00', 'Asia/Jakarta'));

        $this->actingAs($student)
            ->get(route('mahasiswa.logbooks.word'))
            ->assertForbidden();
    }

    public function test_logbook_page_shows_export_actions_only_when_window_opens(): void
    {
        $student = $this->makeStudent('2026-11-01', '2026-12-31');

        $this->travelTo(Carbon::parse('2026-12-23 10:00:00', 'Asia/Jakarta'));

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->assertSee('Export dibuka')
            ->assertDontSee(route('mahasiswa.logbooks.word'));

        $this->travelTo(Carbon::parse('2026-12-24 10:00:00', 'Asia/Jakarta'));

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->assertSee(route('mahasiswa.logbooks.word'))
            ->assertSee(route('mahasiswa.logbooks.pdf'));
    }

    public function test_student_without_internship_profile_cannot_download_logbook(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('mahasiswa.logbooks.word'))
            ->assertForbidden();
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
