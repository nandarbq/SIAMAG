<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\Attendance\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceCameraTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_page_uses_live_camera_without_file_picker(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($student)->get(route('mahasiswa.attendance'));

        $response->assertOk();
        $response->assertSee('attendanceCamera', false);
        $response->assertSee('x-ref="video"', false);
        $response->assertSee('Kamera belum aktif');
        $response->assertSee('Mulai Absen');
        $response->assertSee('@click="startCamera()"', false);
        $response->assertSee('Ambil foto selfie terlebih dahulu.');
        $response->assertDontSee('type="file"', false);
    }

    public function test_submit_hint_disappears_after_photo_is_uploaded(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(Index::class)
            ->assertSee('Ambil foto selfie terlebih dahulu.')
            ->set('photo', UploadedFile::fake()->image('selfie.jpg', 600, 600))
            ->assertDontSee('Ambil foto selfie terlebih dahulu.')
            ->assertSee('x-bind:disabled="uploading || ! true"', false);
    }

    private function makeStudent(): User
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        return $student;
    }
}
