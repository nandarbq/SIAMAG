<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCameraTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_page_uses_live_camera_without_file_picker(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.attendance'));

        $response->assertOk();
        $response->assertSee('attendanceCamera', false);
        $response->assertSee('x-ref="video"', false);
        $response->assertSee('Ambil Foto');
        $response->assertDontSee('type="file"', false);
    }
}
