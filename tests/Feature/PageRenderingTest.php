<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render_successfully(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $routes = [
            'admin.dashboard',
            'admin.students',
            'admin.students.create',
            'admin.attendances',
            'admin.logbooks',
            'admin.leaves',
            'admin.holidays',
            'admin.announcements',
            'admin.settings',
            'admin.reports',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.students.show', $student))->assertOk();
        $this->actingAs($admin)->get(route('admin.students.edit', $student))->assertOk();
    }

    public function test_student_pages_render_successfully(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $routes = [
            'mahasiswa.dashboard',
            'mahasiswa.attendance',
            'mahasiswa.logbooks',
            'mahasiswa.leaves',
            'mahasiswa.profile',
        ];

        foreach ($routes as $route) {
            $this->actingAs($student)->get(route($route))->assertOk();
        }
    }

    public function test_authenticated_layout_includes_app_confirm_dialog(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.dashboard'));

        $response->assertOk();
        $response->assertSee('x-data="confirmDialog"', false);
        $response->assertSee('@app-confirm.window="show($event.detail)"', false);
        $response->assertSee('aria-labelledby="app-confirm-title"', false);
        $response->assertDontSee('wire:confirm', false);
    }
}
