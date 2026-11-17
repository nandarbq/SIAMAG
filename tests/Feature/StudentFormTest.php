<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Students\Form as StudentForm;
use App\Models\InternGroup;
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

    public function test_end_date_must_not_be_before_start_date(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();

        Livewire::actingAs($admin)
            ->test(StudentForm::class)
            ->set('name', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'secret-password')
            ->set('nim', '20260001')
            ->set('intern_group_id', $group->id)
            ->set('major', 'Teknik Informatika')
            ->set('semester', '6')
            ->set('start_date', '2026-12-31')
            ->set('end_date', '2026-11-01')
            ->call('save')
            ->assertHasErrors(['end_date']);
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
}
