<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Settings\AdminAccounts;
use App\Livewire\Admin\Settings\AdminForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_admin_form(): void
    {
        $this->get(route('admin.settings.admins.create'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_admin_account_from_dedicated_form(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class)
            ->set('name', 'Administrator Baru')
            ->set('email', 'admin.baru@example.com')
            ->set('password', 'password-baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.settings'));

        $createdAdmin = User::query()->where('email', 'admin.baru@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $createdAdmin->id,
            'name' => 'Administrator Baru',
            'email' => 'admin.baru@example.com',
            'role' => UserRole::Admin->value,
            'nim' => null,
            'is_active' => true,
        ]);
        $this->assertTrue(Hash::check('password-baru', $createdAdmin->password));
        $this->assertDatabaseMissing('users', ['id' => $createdAdmin->id, 'role' => UserRole::Mahasiswa->value]);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Akun administrator berhasil dibuat.');
    }

    public function test_duplicate_admin_email_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $existingAdmin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class)
            ->set('name', 'Administrator Duplikat')
            ->set('email', $existingAdmin->email)
            ->set('password', 'password-baru')
            ->call('save')
            ->assertHasErrors(['email' => 'Email sudah digunakan akun lain.']);

        $this->assertDatabaseMissing('users', ['name' => 'Administrator Duplikat']);
    }

    public function test_short_password_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class)
            ->set('name', 'Administrator Password Pendek')
            ->set('email', 'admin.pendek@example.com')
            ->set('password', 'pendek')
            ->call('save')
            ->assertHasErrors(['password' => 'Password minimal 8 karakter.']);

        $this->assertDatabaseMissing('users', ['email' => 'admin.pendek@example.com']);
    }

    public function test_admin_can_edit_another_admin_without_changing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create([
            'name' => 'Admin Lama',
            'email' => 'admin.lama@example.com',
        ]);
        $originalPassword = $target->password;

        Livewire::actingAs($admin)
            ->test(AdminForm::class, ['admin' => $target])
            ->assertSet('name', 'Admin Lama')
            ->assertSet('email', 'admin.lama@example.com')
            ->assertSet('is_active', true)
            ->assertSee('Ubah Administrator')
            ->assertSee('Simpan Perubahan')
            ->set('name', 'Admin Baru')
            ->set('email', 'admin.baru@example.com')
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.settings'));

        $updatedAdmin = $target->fresh();

        $this->assertSame('Admin Baru', $updatedAdmin->name);
        $this->assertSame('admin.baru@example.com', $updatedAdmin->email);
        $this->assertFalse($updatedAdmin->is_active);
        $this->assertSame(UserRole::Admin, $updatedAdmin->role);
        $this->assertNull($updatedAdmin->nim);
        $this->assertSame($originalPassword, $updatedAdmin->password);
    }

    public function test_admin_can_update_admin_password_and_keep_current_email(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class, ['admin' => $target])
            ->set('password', 'password-baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.settings'));

        $updatedAdmin = $target->fresh();

        $this->assertTrue(Hash::check('password-baru', $updatedAdmin->password));
        $this->assertFalse(Hash::check('password', $updatedAdmin->password));
        $this->assertSame($target->email, $updatedAdmin->email);
    }

    public function test_duplicate_email_is_rejected_when_editing_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class, ['admin' => $target])
            ->set('email', $otherAdmin->email)
            ->call('save')
            ->assertHasErrors(['email' => 'Email sudah digunakan akun lain.']);

        $this->assertSame($target->email, $target->fresh()->email);
    }

    public function test_short_password_is_rejected_when_editing_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();
        $originalPassword = $target->password;

        Livewire::actingAs($admin)
            ->test(AdminForm::class, ['admin' => $target])
            ->set('password', 'pendek')
            ->call('save')
            ->assertHasErrors(['password' => 'Password minimal 8 karakter.']);

        $this->assertSame($originalPassword, $target->fresh()->password);
    }

    public function test_admin_cannot_change_own_status_when_editing(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class, ['admin' => $admin])
            ->set('is_active', false)
            ->call('save')
            ->assertHasErrors(['is_active' => 'Tidak dapat mengubah status akun sendiri.']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_edit_form_rejects_non_admin_user(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.admins.edit', $student))
            ->assertNotFound();
    }

    public function test_admin_form_renders_password_visibility_toggle(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminForm::class)
            ->assertSee('Tampilkan password', false)
            ->assertSee('x-data="{ show: false }"', false)
            ->assertSee('wire:model="password"', false);
    }

    public function test_admin_can_deactivate_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminAccounts::class)
            ->call('toggleActive', $target->id)
            ->assertHasNoErrors()
            ->assertSee('Akun administrator dinonaktifkan.');

        $this->assertFalse($target->fresh()->is_active);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_activate_inactive_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->inactive()->create();

        Livewire::actingAs($admin)
            ->test(AdminAccounts::class)
            ->call('toggleActive', $target->id)
            ->assertHasNoErrors()
            ->assertSee('Akun administrator diaktifkan.');

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_cannot_change_own_status(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminAccounts::class)
            ->call('toggleActive', $admin->id)
            ->assertHasErrors(['admin_'.$admin->id => 'Tidak dapat mengubah status akun sendiri.']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_settings_page_links_to_admin_form_and_lists_admins(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Akun Administrator')
            ->assertSee('Tambah Admin')
            ->assertSee(route('admin.settings.admins.create'), false)
            ->assertSee(route('admin.settings.admins.edit', $admin), false)
            ->assertSee('wire:click="toggleActive('.$admin->id.')"', false)
            ->assertDontSee('wire:click="toggleForm"', false);
    }
}
