<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Setting;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const OFFICE_LAT = -8.2192;

    private const OFFICE_LNG = 114.3692;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 07:00:00', 'Asia/Jakarta'));
        Storage::fake('local');

        Setting::put('work_start', '07:30');
        Setting::put('work_end', '16:00');
        Setting::put('late_tolerance_min', '15');
        Setting::put('office_lat', (string) self::OFFICE_LAT);
        Setting::put('office_lng', (string) self::OFFICE_LNG);
        Setting::put('radius_m', '150');
        Setting::put('max_accuracy_m', '100');
        Setting::put('require_geofence', '1');
        Setting::put('require_selfie', '1');
    }

    public function test_student_can_check_in_inside_the_geofence(): void
    {
        $student = $this->makeStudent();

        $attendance = app(AttendanceService::class)->checkIn($student, $this->payload());

        $this->assertSame(AttendanceStatus::Hadir, $attendance->status);
        $this->assertNotNull($attendance->check_in_at);
        $this->assertNotNull($attendance->check_in_photo);
        Storage::disk('local')->assertExists($attendance->check_in_photo);
    }

    public function test_late_check_in_is_marked_as_late(): void
    {
        $this->travel(1)->hours();

        $attendance = app(AttendanceService::class)->checkIn($this->makeStudent(), $this->payload());

        $this->assertSame(AttendanceStatus::Terlambat, $attendance->status);
        $this->assertSame(15, $attendance->late_minutes);
    }

    public function test_check_in_outside_the_geofence_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->checkIn($this->makeStudent(), $this->payload([
            'lat' => self::OFFICE_LAT + 0.01,
        ]));
    }

    public function test_check_in_outside_the_geofence_is_allowed_when_disabled(): void
    {
        Setting::put('require_geofence', '0');

        $attendance = app(AttendanceService::class)->checkIn($this->makeStudent(), $this->payload([
            'lat' => self::OFFICE_LAT + 0.01,
        ]));

        $this->assertSame(AttendanceStatus::Hadir, $attendance->status);
        $this->assertNull($attendance->check_in_distance);
        $this->assertNull($attendance->check_in_accuracy);
    }

    public function test_check_in_without_accuracy_is_rejected_when_geofence_is_required(): void
    {
        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->checkIn($this->makeStudent(), $this->payload([
            'accuracy' => null,
        ]));
    }

    public function test_check_in_with_poor_accuracy_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->checkIn($this->makeStudent(), $this->payload([
            'accuracy' => 500,
        ]));
    }

    public function test_check_in_without_selfie_is_rejected_when_required(): void
    {
        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->checkIn($this->makeStudent(), $this->payload([
            'photo' => null,
        ]));
    }

    public function test_check_out_requires_a_previous_check_in(): void
    {
        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->checkOut($this->makeStudent(), $this->payload());
    }

    public function test_student_can_check_out_after_check_in(): void
    {
        $student = $this->makeStudent();
        $service = app(AttendanceService::class);

        $service->checkIn($student, $this->payload());
        $this->travelTo(Carbon::parse('2026-11-09 16:05:00', 'Asia/Jakarta'));

        $attendance = $service->checkOut($student, $this->payload());

        $this->assertNotNull($attendance->check_out_at);
        $this->assertNotNull($attendance->check_out_photo);
        Storage::disk('local')->assertExists($attendance->check_out_photo);
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'lat' => self::OFFICE_LAT,
            'lng' => self::OFFICE_LNG,
            'accuracy' => 10,
            'photo' => UploadedFile::fake()->image('selfie.jpg', 600, 600),
        ], $overrides);
    }
}
