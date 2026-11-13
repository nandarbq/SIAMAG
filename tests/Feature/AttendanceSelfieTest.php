<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceSelfieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_student_can_upload_selfie_and_receive_a_token(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($student)
            ->postJson(route('attendance.selfie'), [
                'photo' => $this->selfieDataUri(),
            ]);

        $response->assertOk()->assertJsonStructure(['token']);

        $payload = json_decode(Crypt::decryptString($response->json('token')), true);

        $this->assertSame($student->id, $payload['user_id']);
        $this->assertStringStartsWith('attendance/'.$student->id.'/', $payload['path']);
        Storage::disk('local')->assertExists($payload['path']);
    }

    public function test_student_can_upload_high_resolution_jpeg_selfie(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($student)
            ->postJson(route('attendance.selfie'), [
                'photo' => $this->selfieDataUri(960, 960),
            ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_student_can_upload_png_selfie(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($student)
            ->postJson(route('attendance.selfie'), [
                'photo' => $this->selfieDataUri(600, 600, 'png'),
            ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_selfie_upload_rejects_invalid_image_data(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->postJson(route('attendance.selfie'), [
                'photo' => 'data:image/jpeg;base64,not-an-image',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_selfie_upload_rejects_non_image_binary(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->postJson(route('attendance.selfie'), [
                'photo' => 'data:image/jpeg;base64,'.base64_encode('not an image'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_selfie_upload_rejects_unsupported_mime_type(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->postJson(route('attendance.selfie'), [
                'photo' => 'data:image/gif;base64,'.base64_encode('not an image'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_admin_cannot_upload_attendance_selfie(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('attendance.selfie'), [
                'photo' => $this->selfieDataUri(),
            ])
            ->assertForbidden();
    }

    private function selfieDataUri(int $width = 600, int $height = 600, string $format = 'jpg'): string
    {
        $image = UploadedFile::fake()->image('selfie.'.$format, $width, $height)->getContent();
        $mimeType = $format === 'png' ? 'image/png' : 'image/jpeg';

        return 'data:'.$mimeType.';base64,'.base64_encode($image);
    }
}
