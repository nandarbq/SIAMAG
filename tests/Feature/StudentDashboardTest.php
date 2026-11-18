<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Livewire\Mahasiswa\Dashboard as StudentDashboard;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));
    }

    public function test_dashboard_uses_office_name_and_surfaces_active_announcements(): void
    {
        Setting::put('office_name', 'BMKG Banyuwangi');

        $student = $this->makeStudent();

        Announcement::factory()->create([
            'title' => 'Jadwal Apel Senin',
            'body' => 'Apel pagi dilaksanakan pukul 07.30 di halaman kantor.',
            'is_active' => true,
            'published_at' => Carbon::parse('2026-11-09 08:00:00'),
        ]);

        Announcement::factory()->create([
            'title' => 'Pengumuman Nonaktif',
            'is_active' => false,
            'published_at' => Carbon::parse('2026-11-10 08:00:00'),
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Magang di')
            ->assertSee('BMKG Banyuwangi')
            ->assertSee('Universitas Jember')
            ->assertSee('Jadwal Apel Senin')
            ->assertDontSee('Pengumuman Nonaktif')
            ->assertDontSee('progres magang Anda di Universitas Jember');
    }

    public function test_dashboard_hides_announcement_masthead_without_announcements(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertDontSee('Pusat Informasi')
            ->assertDontSee('Pengumuman terbaru');
    }

    public function test_dashboard_builds_weekly_activity_statuses(): void
    {
        $student = $this->makeStudent();

        Attendance::factory()->for($student)->create([
            'date' => '2026-11-10',
            'status' => AttendanceStatus::Hadir,
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertViewHas('week', function (array $week): bool {
                $today = collect($week)->firstWhere('isToday', true);

                return $today !== null && $today['status_label'] === 'Hadir';
            });
    }

    public function test_monthly_attendance_includes_the_last_day_of_the_month(): void
    {
        $this->travelTo(Carbon::parse('2026-11-30 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        Attendance::factory()->for($student)->create([
            'date' => '2026-11-30',
            'status' => AttendanceStatus::Hadir,
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertViewHas('hadir', 1);
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
}
