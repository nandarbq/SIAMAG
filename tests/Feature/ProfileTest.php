<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_profile_renders_password_visibility_toggles(): void
    {
        $student = User::factory()->mahasiswa()->create();

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->assertSee('Tampilkan password', false)
            ->assertSee('x-data="{ show: false }"', false)
            ->assertSee('wire:model="currentPassword"', false)
            ->assertSee('wire:model="passwordConfirmation"', false);
    }

    public function test_student_can_update_profile_information(): void
    {
        $student = User::factory()->mahasiswa()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
        ]);

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('name', 'Nama Baru')
            ->set('email', 'baru@example.com')
            ->set('phone', '081234567890')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $student->refresh();

        $this->assertSame('Nama Baru', $student->name);
        $this->assertSame('baru@example.com', $student->email);
        $this->assertSame('081234567890', $student->phone);
    }

    public function test_student_can_upload_profile_photo(): void
    {
        $student = User::factory()->mahasiswa()->create();

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('avatar.jpg', 300, 300))
            ->call('updatePhoto')
            ->assertHasNoErrors();

        $student->refresh();

        $this->assertNotNull($student->photo_path);
        Storage::disk('local')->assertExists($student->photo_path);
    }

    public function test_profile_page_renders_avatar_cropper(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('mahasiswa.profile'))
            ->assertOk()
            ->assertSee('avatarCropper', false)
            ->assertSee('Sesuaikan foto', false);
    }

    public function test_replacing_profile_photo_deletes_the_old_file(): void
    {
        Storage::disk('local')->put('avatars/old.jpg', 'old-image-bytes');

        $student = User::factory()->mahasiswa()->create([
            'photo_path' => 'avatars/old.jpg',
        ]);

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('new.png', 300, 300))
            ->call('updatePhoto')
            ->assertHasNoErrors();

        $student->refresh();

        $this->assertNotSame('avatars/old.jpg', $student->photo_path);
        Storage::disk('local')->assertMissing('avatars/old.jpg');
        Storage::disk('local')->assertExists($student->photo_path);
    }

    public function test_student_can_remove_profile_photo(): void
    {
        Storage::disk('local')->put('avatars/me.jpg', 'image-bytes');

        $student = User::factory()->mahasiswa()->create([
            'photo_path' => 'avatars/me.jpg',
        ]);

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->call('removePhoto')
            ->assertHasNoErrors();

        $this->assertNull($student->fresh()->photo_path);
        Storage::disk('local')->assertMissing('avatars/me.jpg');
    }

    public function test_profile_photo_must_be_an_image(): void
    {
        $student = User::factory()->mahasiswa()->create();

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->call('updatePhoto')
            ->assertHasErrors(['photo']);

        $this->assertNull($student->fresh()->photo_path);
    }

    public function test_profile_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $student = User::factory()->mahasiswa()->create(['email' => 'student@example.com']);

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('email', 'taken@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['email']);
    }

    public function test_student_can_change_password(): void
    {
        $student = User::factory()->mahasiswa()->create(['password' => 'old-password']);

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('currentPassword', 'old-password')
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $student->fresh()->password));
    }

    public function test_password_change_invalidates_sessions_with_the_old_password_hash(): void
    {
        $student = User::factory()->mahasiswa()->create(['password' => 'old-password']);
        $oldPasswordHash = $student->getAuthPassword();

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('currentPassword', 'old-password')
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->actingAs($student->fresh())
            ->withSession([
                'password_hash_'.Auth::getDefaultDriver() => $oldPasswordHash,
            ])
            ->get(route('mahasiswa.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $student = User::factory()->mahasiswa()->create(['password' => 'old-password']);

        Livewire::actingAs($student)
            ->test(Profile::class)
            ->set('currentPassword', 'wrong-password')
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('old-password', $student->fresh()->password));
    }
}
