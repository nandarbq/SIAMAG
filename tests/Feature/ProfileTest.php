<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

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
