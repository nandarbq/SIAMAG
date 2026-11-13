<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneAttendancePhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-13 02:00:00', 'Asia/Jakarta'));
        Storage::fake('local');
    }

    public function test_command_deletes_old_attendance_photos_and_clears_paths(): void
    {
        $student = User::factory()->mahasiswa()->create();

        Storage::disk('local')->put('attendance/'.$student->id.'/old-in.jpg', 'old-in');
        Storage::disk('local')->put('attendance/'.$student->id.'/old-out.jpg', 'old-out');
        Storage::disk('local')->put('attendance/'.$student->id.'/recent-in.jpg', 'recent-in');

        $old = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-10-01',
            'status' => AttendanceStatus::Hadir,
            'check_in_photo' => 'attendance/'.$student->id.'/old-in.jpg',
            'check_out_photo' => 'attendance/'.$student->id.'/old-out.jpg',
        ]);

        $recent = Attendance::query()->create([
            'user_id' => $student->id,
            'date' => '2026-11-12',
            'status' => AttendanceStatus::Hadir,
            'check_in_photo' => 'attendance/'.$student->id.'/recent-in.jpg',
        ]);

        $this->artisan('attendance:prune-photos')->assertSuccessful();

        Storage::disk('local')->assertMissing($old->check_in_photo);
        Storage::disk('local')->assertMissing($old->check_out_photo);
        Storage::disk('local')->assertExists($recent->check_in_photo);

        $this->assertNull($old->fresh()->check_in_photo);
        $this->assertNull($old->fresh()->check_out_photo);
        $this->assertSame($recent->check_in_photo, $recent->fresh()->check_in_photo);
    }

    public function test_command_deletes_orphan_files_older_than_the_retention_period(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $oldOrphan = 'attendance/'.$student->id.'/old-orphan.jpg';
        $recentOrphan = 'attendance/'.$student->id.'/recent-orphan.jpg';

        Storage::disk('local')->put($oldOrphan, 'old');
        Storage::disk('local')->put($recentOrphan, 'recent');

        touch(Storage::disk('local')->path($oldOrphan), now()->subDays(31)->timestamp);

        $this->artisan('attendance:prune-photos')->assertSuccessful();

        Storage::disk('local')->assertMissing($oldOrphan);
        Storage::disk('local')->assertExists($recentOrphan);
    }

    public function test_command_accepts_a_custom_retention_period(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $path = 'attendance/'.$student->id.'/eight-days-old.jpg';

        Storage::disk('local')->put($path, 'old');
        touch(Storage::disk('local')->path($path), now()->subDays(8)->timestamp);

        $this->artisan('attendance:prune-photos', ['--days' => 7])->assertSuccessful();

        Storage::disk('local')->assertMissing($path);
    }
}
