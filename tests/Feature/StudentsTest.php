<?php

namespace Tests\Feature;

use App\Livewire\Admin\Students\Index as StudentIndex;
use App\Models\Attendance;
use App\Models\InternProfile;
use App\Models\LeaveRequest;
use App\Models\Logbook;
use App\Models\User;
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
        $student = User::factory()->mahasiswa()->create();

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

        foreach ([$attendance->check_in_photo, $attendance->check_out_photo, $leave->attachment_path, $logbook->attachment_path] as $path) {
            Storage::disk('local')->put($path, 'file-bytes');
        }

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('delete', $student);

        Storage::disk('local')->assertMissing($attendance->check_in_photo);
        Storage::disk('local')->assertMissing($attendance->check_out_photo);
        Storage::disk('local')->assertMissing($leave->attachment_path);
        Storage::disk('local')->assertMissing($logbook->attachment_path);
    }

    public function test_intern_profile_user_id_must_be_unique(): void
    {
        $student = User::factory()->mahasiswa()->create();

        InternProfile::factory()->for($student)->create();

        $this->expectException(QueryException::class);

        InternProfile::factory()->for($student)->create();
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
}
