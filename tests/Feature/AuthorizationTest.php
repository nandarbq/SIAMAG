<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_student_cannot_access_admin_pages(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_student_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('mahasiswa.dashboard'))
            ->assertForbidden();
    }

    public function test_student_can_access_student_pages(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk();
    }
}
