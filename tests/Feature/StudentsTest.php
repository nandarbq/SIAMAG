<?php

namespace Tests\Feature;

use App\Livewire\Admin\Students\Index as StudentIndex;
use App\Models\Attendance;
use App\Models\InternProfile;
use App\Models\LeaveRequest;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_student_account_status(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('toggleActive', $student);

        $this->assertFalse($student->fresh()->is_active);
    }

    public function test_admin_can_delete_a_student(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('delete', $student);

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_deleting_student_removes_stored_files(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create([
            'photo_path' => 'avatars/student-delete/profile.jpg',
        ]);

        $attendance = Attendance::factory()->for($student)->create([
            'check_in_photo' => 'attendance/'.$student->id.'/in.jpg',
            'check_out_photo' => 'attendance/'.$student->id.'/out.jpg',
        ]);

        $leave = LeaveRequest::factory()->for($student)->create([
            'attachment_path' => 'leave-attachments/'.$student->id.'/surat.pdf',
        ]);

        $logbook = Logbook::factory()->for($student)->create([
            'attachment_path' => 'logbooks/'.$student->id.'/lampiran.pdf',
        ]);

        foreach ([$attendance->check_in_photo, $attendance->check_out_photo, $leave->attachment_path, $logbook->attachment_path, $student->photo_path] as $path) {
            Storage::disk('local')->put($path, 'file-bytes');
        }

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('delete', $student);

        Storage::disk('local')->assertMissing($attendance->check_in_photo);
        Storage::disk('local')->assertMissing($attendance->check_out_photo);
        Storage::disk('local')->assertMissing($leave->attachment_path);
        Storage::disk('local')->assertMissing($logbook->attachment_path);
        Storage::disk('local')->assertMissing($student->photo_path);
    }

    public function test_admin_student_views_render_profile_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create([
            'photo_path' => 'avatars/student.jpg',
        ]);

        $photoUrl = $student->avatarUrl();

        $this->actingAs($admin)
            ->get(route('admin.students'))
            ->assertOk()
            ->assertSee($photoUrl, false);

        $this->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee($photoUrl, false);
    }

    public function test_intern_profile_user_id_must_be_unique(): void
    {
        $student = User::factory()->mahasiswa()->create();

        InternProfile::factory()->for($student)->create();

        $this->expectException(QueryException::class);

        InternProfile::factory()->for($student)->create();
    }

    public function test_search_changes_reset_the_status_filter(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->set('status', 'active')
            ->set('search', 'Budi')
            ->assertSet('status', '')
            ->set('status', 'active')
            ->set('search', '')
            ->assertSet('status', '');
    }

    public function test_student_list_can_be_searched(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->mahasiswa()->create(['name' => 'Budi Santoso']);
        User::factory()->mahasiswa()->create(['name' => 'Siti Aminah']);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->set('search', 'Budi')
            ->assertViewHas('students', function ($students) {
                return $students->total() === 1 && $students->first()->name === 'Budi Santoso';
            });
    }

    public function test_admin_student_views_mask_the_nim(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create(['nim' => '20260001']);

        $this->actingAs($admin)
            ->get(route('admin.students'))
            ->assertOk()
            ->assertSee('20••••01')
            ->assertDontSee('NIM 20260001');

        $this->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('20••••01')
            ->assertDontSee('NIM 20260001');
    }

    public function test_completed_students_remain_listed_and_can_be_filtered(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->admin()->create();

        $finished = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Selesai',
            'is_active' => true,
        ]);
        InternProfile::factory()->for($finished)->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $ongoing = User::factory()->mahasiswa()->create(['name' => 'Mahasiswa Berjalan']);
        InternProfile::factory()->for($ongoing)->create([
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-31',
        ]);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->assertViewHas('students', fn ($students) => $students->total() === 2)
            ->assertSee('Selesai');

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->set('status', 'completed')
            ->assertViewHas('students', fn ($students) => $students->total() === 1
                && $students->first()->is($finished));
    }

    public function test_completed_student_detail_shows_selesai_badge(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create(['is_active' => true]);
        InternProfile::factory()->for($student)->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Akun Aktif');
    }
}
