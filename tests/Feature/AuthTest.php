<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_login_page_loads_livewire_scripts_for_alpine(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('livewire', false);
    }

    public function test_login_page_uses_calm_institutional_landing_with_login_modal(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Stasiun Meteorologi Banyuwangi')
            ->assertSee('Portal Magang')
            ->assertSee('Absensi, logbook, dan laporan magang dalam satu akun.')
            ->assertSee('Absensi harian')
            ->assertSee('Logbook kegiatan')
            ->assertSee('Laporan magang')
            ->assertSee('Masuk ke SIAMAG')
            ->assertSee('Gunakan akun yang diberikan admin.')
            ->assertSee('Ingat saya')
            ->assertSee('Lupa password?')
            ->assertSee('Sistem Informasi Absensi & Logbook Magang')
            ->assertSee('Tanggal')
            ->assertSee('Zona waktu')
            ->assertSee('WIB / UTC+7')
            ->assertSee('Lokasi')
            ->assertSee('Banyuwangi, Jawa Timur')
            ->assertSee('Stasiun Banyuwangi')
            ->assertSee('auth-radar', false)
            ->assertSee('auth-radar-ticks', false)
            ->assertSee('auth-radar-label', false)
            ->assertSee('auth-radar-blip', false)
            ->assertSee('auth-radar-center', false)
            ->assertSee('--blip-delay', false)
            ->assertSee('auth-card-mark', false)
            ->assertSee('will-change-transform', false)
            ->assertSee('x-transition:enter', false)
            ->assertSee('cubic-bezier(0.16,1,0.3,1)', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('id="login-title"', false)
            ->assertSee('data-login-modal-open="false"', false)
            ->assertDontSee('auth-card-paper', false)
            ->assertDontSee('auth-card-spine', false)
            ->assertDontSee('Magang yang')
            ->assertDontSee('Akses terverifikasi')
            ->assertDontSee('Koneksi aman')
            ->assertDontSee('Sistem operasional')
            ->assertDontSee('Operasional')
            ->assertDontSee('Observasi stasiun')
            ->assertDontSee('Terhubung')
            ->assertSee('images/logo-bmkg.png', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('type="password"', false)
            ->assertSee('x-bind:type', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('action="'.route('login.store').'"', false);
    }

    public function test_login_page_renders_validation_errors_inside_the_card(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Email atau password salah.')
            ->assertSee('value="user@example.com"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('data-login-modal-open="true"', false);
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('mahasiswa.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_student_can_login_and_is_redirected_to_student_dashboard(): void
    {
        $student = User::factory()->mahasiswa()->create([
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('mahasiswa.dashboard'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
