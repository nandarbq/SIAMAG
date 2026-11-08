<?php

namespace Tests\Feature;

use App\Livewire\Admin\Students\Index as StudentIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
