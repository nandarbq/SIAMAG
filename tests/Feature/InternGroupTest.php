<?php

namespace Tests\Feature;

use App\Livewire\Admin\Groups\Form as GroupForm;
use App\Livewire\Admin\Groups\Index as GroupIndex;
use App\Livewire\Admin\Students\Index as StudentIndex;
use App\Models\InternGroup;
use App\Models\InternProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InternGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-17 09:00:00', 'Asia/Jakarta'));
    }

    public function test_admin_can_create_an_intern_group(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GroupForm::class, ['embedded' => true])
            ->set('code', 'MAG-2026-001')
            ->set('name', 'Kelompok 1 - Poliwangi')
            ->set('institution', 'Politeknik Negeri Banyuwangi')
            ->set('division', 'Divisi IT')
            ->set('field_supervisor', 'Pak Slamet')
            ->set('supervisor_phone', '081111111111')
            ->set('start_date', '2026-11-01')
            ->set('end_date', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('group-saved');

        $this->assertDatabaseHas('intern_groups', [
            'code' => 'MAG-2026-001',
            'name' => 'Kelompok 1 - Poliwangi',
            'institution' => 'Politeknik Negeri Banyuwangi',
            'field_supervisor' => 'Pak Slamet',
        ]);
    }

    public function test_group_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        InternGroup::factory()->create(['code' => 'MAG-2026-001']);

        Livewire::actingAs($admin)
            ->test(GroupForm::class)
            ->set('code', 'MAG-2026-001')
            ->set('name', 'Kelompok Duplikat')
            ->set('institution', 'Universitas Jember')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    public function test_group_end_date_must_not_be_before_start_date(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GroupForm::class)
            ->set('code', 'MAG-2026-002')
            ->set('name', 'Kelompok Tanggal')
            ->set('institution', 'Universitas Jember')
            ->set('start_date', '2026-12-31')
            ->set('end_date', '2026-11-01')
            ->call('save')
            ->assertHasErrors(['end_date']);
    }

    public function test_updating_group_synchronizes_member_profiles(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create(['field_supervisor' => 'Pembimbing Lama']);
        $profile = InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
            'field_supervisor' => 'Pembimbing Lama',
        ]);

        Livewire::actingAs($admin)
            ->test(GroupForm::class, ['group' => $group])
            ->set('field_supervisor', 'Pembimbing Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Pembimbing Baru', $profile->fresh()->field_supervisor);
    }

    public function test_group_with_members_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();
        InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
        ]);

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->call('delete', $group)
            ->assertDispatched('group-delete-failed');

        $this->assertModelExists($group);
    }

    public function test_empty_group_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->call('delete', $group)
            ->assertDispatched('group-deleted');

        $this->assertModelMissing($group);
    }

    public function test_group_search_changes_reset_the_status_filter(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('status', 'active')
            ->set('search', 'Poliwangi')
            ->assertSet('status', '');
    }

    public function test_group_management_lives_in_students_page_tabs(): void
    {
        $admin = User::factory()->admin()->create();
        InternGroup::factory()->create(['name' => 'Kelompok Tab']);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->assertSet('tab', 'students')
            ->call('switchTab', 'groups')
            ->assertSet('tab', 'groups')
            ->assertSee('Tambah Kelompok')
            ->assertSee('Kelompok Tab');
    }

    public function test_clicking_groups_tab_always_opens_the_group_list(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create(['name' => 'Kelompok Harus Tampil']);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('showGroup', $group->id)
            ->assertSet('groupView', 'show')
            ->call('switchTab', 'students')
            ->call('switchTab', 'groups')
            ->assertSet('tab', 'groups')
            ->assertSet('groupView', 'list')
            ->assertSet('groupId', null)
            ->assertSee('Kelompok Harus Tampil');
    }

    public function test_students_page_can_open_group_create_form_and_return_to_list(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('switchTab', 'groups')
            ->call('createGroup')
            ->assertSet('groupView', 'form')
            ->assertSet('groupId', null)
            ->dispatch('group-saved', message: 'Kelompok magang berhasil ditambahkan.')
            ->assertSet('groupView', 'list')
            ->assertSee('Kelompok magang berhasil ditambahkan.');
    }

    public function test_old_group_route_redirects_to_students_tab(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.groups'))
            ->assertRedirect(route('admin.students', ['tab' => 'groups']));
    }

    public function test_group_query_parameters_open_the_matching_tab_view(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create(['name' => 'Kelompok Deep Link']);

        $this->actingAs($admin)
            ->get(route('admin.students', ['tab' => 'groups', 'group' => $group->id]))
            ->assertOk()
            ->assertSee('Kelompok Deep Link');

        $this->actingAs($admin)
            ->get(route('admin.students', ['tab' => 'groups', 'new' => 1]))
            ->assertOk()
            ->assertSee('Tambah Kelompok');
    }
}
