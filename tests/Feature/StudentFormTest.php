<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Students\Form as StudentForm;
use App\Models\InternGroup;
use App\Models\InternProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StudentFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
    }

    public function test_student_form_renders_password_visibility_toggle(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->assertSee('Tampilkan password', false)
            ->assertSee('x-data="{ show: false }"', false)
            ->assertSee('wire:model="password"', false);
    }

    public function test_student_form_shows_group_first_workflow_and_prompt_when_no_group_exists(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->assertSee('Buat atau pilih kelompok magang lebih dulu', false)
            ->assertSee('Kampus, pembimbing, dan periode terisi otomatis dari kelompok, tetapi dapat disesuaikan per mahasiswa.', false)
            ->assertSee('Belum ada kelompok aktif.', false)
            ->assertSee('Buat kelompok sekarang', false);
    }

    public function test_group_modal_opens_and_closes_without_leaving_the_student_form(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->call('openGroupModal')
            ->assertSet('groupModalOpen', true)
            ->assertSee('Buat kelompok magang')
            ->assertSee('Setelah disimpan, kelompok ini otomatis dipilih untuk mahasiswa.', false)
            ->dispatch('back-to-groups')
            ->assertSet('groupModalOpen', false);
    }

    public function test_created_group_is_selected_and_fills_shared_student_profile_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create([
            'institution' => 'Politeknik Negeri Banyuwangi',
            'field_supervisor' => 'Ibu Dewi',
            'supervisor_phone' => '082222222222',
            'start_date' => Carbon::parse('2026-01-05'),
            'end_date' => Carbon::parse('2026-04-30'),
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->call('openGroupModal')
            ->dispatch('group-saved', message: 'Kelompok magang berhasil ditambahkan.', groupId: $group->id)
            ->assertSet('groupModalOpen', false)
            ->assertSet('intern_group_id', $group->id)
            ->assertSet('institution', 'Politeknik Negeri Banyuwangi')
            ->assertSet('field_supervisor', 'Ibu Dewi')
            ->assertSet('supervisor_phone', '082222222222')
            ->assertSet('start_date', '2026-01-05')
            ->assertSet('end_date', '2026-04-30')
            ->assertSee('Kelompok magang berhasil ditambahkan.');
    }

    public function test_admin_can_create_a_student_with_internship_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create([
            'code' => 'MAG-2026-901',
            'institution' => 'Universitas Jember',
            'field_supervisor' => 'Pak Slamet',
            'supervisor_phone' => '081111111111',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260001')
            ->set('phone', '081234567890')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->set('start_date', '2026-11-01')
            ->set('end_date', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.students'));

        $student = User::query()->where('email', 'budi@example.com')->firstOrFail();

        $this->assertSame(UserRole::Mahasiswa, $student->role);
        $this->assertTrue($student->is_active);
        $this->assertTrue(Hash::check('secret-password', $student->password));
        $this->assertNotNull($student->email_verified_at);
        $this->assertSame('Universitas Jember', $student->internProfile->institution);
        $this->assertSame('Pak Slamet', $student->internProfile->field_supervisor);
        $this->assertSame($group->id, $student->internProfile->intern_group_id);
        $this->assertSame('20260001', $student->nim);
    }

    public function test_student_email_and_nim_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();
        User::factory()->mahasiswa()->create([
            'email' => 'taken@example.com',
            'nim' => '20260001',
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'taken@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260001')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->set('start_date', '2026-11-01')
            ->set('end_date', '2026-12-31')
            ->call('save')
            ->assertHasErrors(['email', 'nim']);
    }

    public function test_admin_can_update_a_student_without_resetting_password(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create([
            'institution' => 'Universitas Jember',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);
        $student = User::factory()->mahasiswa()->create(['password' => 'old-password']);
        $student->internProfile()->create([
            'intern_group_id' => $group->id,
            'institution' => 'Universitas Jember',
            'major' => 'Sistem Informasi',
            'semester' => '4',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class, ['user' => $student])
            ->set('semester', '5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.students'));

        $this->assertSame('5', $student->fresh()->internProfile->semester);
        $this->assertTrue(Hash::check('old-password', $student->fresh()->password));
    }

    public function test_admin_can_customize_group_profile_fields_for_student(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create([
            'code' => 'MAG-2026-902',
            'institution' => 'Universitas Jember',
            'field_supervisor' => 'Pak Slamet',
            'supervisor_phone' => '081111111111',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260002')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->set('institution', 'Institusi Buatan')
            ->set('field_supervisor', 'Ibu Sari')
            ->set('supervisor_phone', '082222222222')
            ->set('start_date', '2026-01-01')
            ->set('end_date', '2026-01-31')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.students'));

        $profile = User::query()->where('email', 'budi@example.com')->firstOrFail()->internProfile;

        $this->assertSame($group->id, $profile->intern_group_id);
        $this->assertSame('Institusi Buatan', $profile->institution);
        $this->assertSame('Ibu Sari', $profile->field_supervisor);
        $this->assertSame('082222222222', $profile->supervisor_phone);
        $this->assertSame('2026-01-01', $profile->start_date->toDateString());
        $this->assertSame('2026-01-31', $profile->end_date->toDateString());
    }

    public function test_internship_profile_fields_are_required_and_dates_must_be_ordered(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260004')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->set('institution', '')
            ->set('start_date', '2026-03-01')
            ->set('end_date', '2026-02-01')
            ->call('save')
            ->assertHasErrors(['institution' => 'required', 'end_date' => 'after_or_equal']);

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_inactive_group_cannot_be_assigned_to_student(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->inactive()->create();

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260003')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->call('save')
            ->assertHasErrors(['intern_group_id']);

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_deep_link_to_inactive_group_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->inactive()->create();

        $this->actingAs($admin)
            ->get(route('admin.students.create', ['group' => $group->id]))
            ->assertNotFound();
    }

    public function test_editing_student_can_keep_their_inactive_group(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->inactive()->create();
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create(array_merge($group->profileAttributes(), [
            'intern_group_id' => $group->id,
            'major' => 'Sistem Informasi',
            'semester' => '4',
        ]));

        Livewire::actingAs($admin)
            ->test(StudentForm::class, ['user' => $student])
            ->set('semester', '5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.students'));

        $this->assertSame($group->id, $student->fresh()->internProfile->intern_group_id);
    }

    public function test_selecting_group_fills_shared_profile_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create([
            'institution' => 'Politeknik Negeri Banyuwangi',
            'field_supervisor' => 'Ibu Dewi',
            'supervisor_phone' => '082222222222',
            'start_date' => Carbon::parse('2026-01-05'),
            'end_date' => Carbon::parse('2026-04-30'),
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('intern_group_id', $group->id)
            ->assertSet('institution', 'Politeknik Negeri Banyuwangi')
            ->assertSet('field_supervisor', 'Ibu Dewi')
            ->assertSet('supervisor_phone', '082222222222')
            ->assertSet('start_date', '2026-01-05')
            ->assertSet('end_date', '2026-04-30');
    }

    public function test_individual_group_rejects_a_second_student(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->individual()->create();
        InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260001')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->call('save')
            ->assertHasErrors(['intern_group_id' => 'Kelompok mandiri hanya bisa memiliki satu mahasiswa. Pilih kelompok lain.']);

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_editing_the_only_member_of_an_individual_group_keeps_its_group(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->individual()->create();
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create(array_merge($group->profileAttributes(), [
            'intern_group_id' => $group->id,
            'major' => 'Sistem Informasi',
            'semester' => '4',
        ]));

        Livewire::actingAs($admin)
            ->test(StudentForm::class, ['user' => $student])
            ->set('semester', '5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.students'));

        $this->assertSame($group->id, $student->fresh()->internProfile->intern_group_id);
        $this->assertSame('5', $student->fresh()->internProfile->semester);
    }

    public function test_student_form_labels_individual_groups(): void
    {
        $admin = User::factory()->admin()->create();
        InternGroup::factory()->individual()->create([
            'code' => 'MAG-2026-IND-777',
            'name' => 'Kelompok Tunggal',
        ]);

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->assertSee('MAG-2026-IND-777 — Kelompok Tunggal · Mandiri');
    }
}
