<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Livewire\Mahasiswa\Dashboard as StudentDashboard;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Logbook;
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
            ->assertSee('Portal Mahasiswa Magang')
            ->assertSee('progres magang Anda di')
            ->assertSee('BMKG Banyuwangi')
            ->assertSee('Universitas Jember')
            ->assertSee('Pengumuman terbaru')
            ->assertSee('Jadwal Apel Senin')
            ->assertSee('Fokus hari ini')
            ->assertSee('Absen masuk belum tercatat.')
            ->assertDontSee('Pengumuman Nonaktif')
            ->assertDontSee('progres magang Anda di Universitas Jember');
    }

    public function test_dashboard_shows_today_focus_without_announcements(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Fokus hari ini')
            ->assertSee('Absen masuk belum tercatat.')
            ->assertDontSee('Pengumuman terbaru');
    }

    public function test_today_focus_describes_excused_attendance_without_check_in(): void
    {
        $student = $this->makeStudent();

        Attendance::factory()->for($student)->create([
            'date' => '2026-11-10',
            'status' => AttendanceStatus::Sakit,
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Izin atau sakit hari ini tercatat.')
            ->assertSee('Sakit tercatat')
            ->assertDontSee('Sudah masuk')
            ->assertDontSee('Belum ada catatan');
    }

    public function test_today_focus_reminds_student_to_fill_logbook(): void
    {
        $student = $this->makeStudent();

        Attendance::factory()->for($student)->create([
            'date' => '2026-11-10',
            'status' => AttendanceStatus::Hadir,
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Fokus hari ini')
            ->assertSee('Logbook pekan ini belum diisi.')
            ->assertSee('Isi logbook');
    }

    public function test_today_focus_marks_completed_day(): void
    {
        $student = $this->makeStudent();

        Attendance::factory()->for($student)->create([
            'date' => '2026-11-10',
            'status' => AttendanceStatus::Hadir,
        ]);

        Logbook::factory()->for($student)->create([
            'date' => '2026-11-10',
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Administrasi pekan ini beres.')
            ->assertSee('Buka logbook');
    }

    public function test_dashboard_hides_expand_control_for_single_announcement(): void
    {
        $student = $this->makeStudent();

        Announcement::factory()->create([
            'title' => 'Satu Pengumuman',
            'is_active' => true,
            'published_at' => Carbon::parse('2026-11-09 08:00:00'),
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Pengumuman terbaru')
            ->assertSee('Satu Pengumuman')
            ->assertDontSee('Lihat semua')
            ->assertDontSee('1 info');
    }

    public function test_dashboard_shows_expand_control_for_multiple_announcements(): void
    {
        $student = $this->makeStudent();

        Announcement::factory()->create([
            'title' => 'Pengumuman Pertama',
            'is_active' => true,
            'published_at' => Carbon::parse('2026-11-09 08:00:00'),
        ]);

        Announcement::factory()->create([
            'title' => 'Pengumuman Kedua',
            'is_active' => true,
            'published_at' => Carbon::parse('2026-11-10 08:00:00'),
        ]);

        Livewire::actingAs($student)
            ->test(StudentDashboard::class)
            ->assertSee('Pengumuman terbaru')
            ->assertSee('2 info')
            ->assertSee('Lihat semua')
            ->assertSee('Pengumuman Pertama')
            ->assertSee('Pengumuman Kedua');
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
