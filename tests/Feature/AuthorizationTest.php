<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
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

    public function test_inactive_admin_cannot_access_admin_pages(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_middleware_is_applied_to_livewire_updates(): void
    {
        $this->assertContains(
            EnsureUserIsActive::class,
            app(PersistentMiddleware::class)->getPersistentMiddleware(),
        );
    }
}
