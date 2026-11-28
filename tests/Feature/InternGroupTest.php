<?php

namespace Tests\Feature;

use App\Livewire\Admin\Groups\Form as GroupForm;
use App\Livewire\Admin\Groups\Index as GroupIndex;
use App\Livewire\Admin\Groups\Show as GroupShow;
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
            ->assertDispatched('group-saved', function (string $event, array $params): bool {
                return isset($params['groupId'])
                    && InternGroup::query()->whereKey($params['groupId'])->where('code', 'MAG-2026-001')->exists();
            });

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

    public function test_admin_can_mark_a_new_group_as_individual(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GroupForm::class, ['embedded' => true])
            ->set('name', 'Kelompok Tunggal')
            ->set('institution', 'Politeknik Negeri Banyuwangi')
            ->set('is_individual', true)
            ->set('start_date', '2026-11-01')
            ->set('end_date', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('group-saved');

        $this->assertDatabaseHas('intern_groups', [
            'code' => 'MAG-2026-IND-001',
            'is_individual' => true,
        ]);
    }

    public function test_group_with_one_member_can_be_marked_individual(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();
        InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
        ]);

        Livewire::actingAs($admin)
            ->test(GroupForm::class, ['group' => $group])
            ->set('is_individual', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($group->fresh()->is_individual);
    }

    public function test_group_with_multiple_members_cannot_be_marked_individual(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();
        InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
        ]);
        InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
        ]);

        Livewire::actingAs($admin)
            ->test(GroupForm::class, ['group' => $group])
            ->set('is_individual', true)
            ->call('save')
            ->assertHasErrors(['is_individual' => 'Kelompok dengan lebih dari satu anggota tidak bisa ditandai sebagai magang mandiri.']);

        $this->assertFalse($group->fresh()->is_individual);
    }

    public function test_individual_filter_only_returns_individual_groups(): void
    {
        $admin = User::factory()->admin()->create();
        $individualGroup = InternGroup::factory()->individual()->create(['name' => 'Kelompok Tunggal']);
        InternGroup::factory()->create(['name' => 'Kelompok Biasa']);

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('type', 'individual')
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 1 && $groups->first()->is($individualGroup))
            ->assertSee('Mandiri');

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('type', 'group')
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 1 && ! $groups->first()->is_individual);
    }

    public function test_group_search_changes_reset_the_type_filter(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('type', 'individual')
            ->set('search', 'Poliwangi')
            ->assertSet('type', '');
    }

    public function test_group_completion_is_derived_from_end_date(): void
    {
        $ongoing = InternGroup::factory()->create(['end_date' => '2026-12-31']);
        $endingToday = InternGroup::factory()->create(['end_date' => '2026-11-17']);
        $finished = InternGroup::factory()->create(['end_date' => '2026-11-16']);

        $this->assertFalse($ongoing->isCompleted());
        $this->assertFalse($endingToday->isCompleted());
        $this->assertTrue($finished->isCompleted());
    }

    public function test_completed_groups_remain_listed_and_can_be_filtered(): void
    {
        $admin = User::factory()->admin()->create();

        $finished = InternGroup::factory()->create([
            'name' => 'Kelompok Selesai',
            'end_date' => '2026-11-16',
        ]);
        InternGroup::factory()->create([
            'name' => 'Kelompok Berjalan',
            'end_date' => '2026-12-31',
        ]);

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 2)
            ->assertSee('Selesai');

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('status', 'completed')
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 1
                && $groups->first()->is($finished));

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('status', 'active')
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 1
                && $groups->first()->name === 'Kelompok Berjalan');
    }

    public function test_inactive_filter_excludes_completed_groups(): void
    {
        $admin = User::factory()->admin()->create();

        InternGroup::factory()->create([
            'name' => 'Kelompok Selesai Nonaktif',
            'is_active' => false,
            'end_date' => '2026-11-16',
        ]);
        $ongoing = InternGroup::factory()->create([
            'name' => 'Kelompok Berjalan Nonaktif',
            'is_active' => false,
            'end_date' => '2026-12-31',
        ]);

        Livewire::actingAs($admin)
            ->test(GroupIndex::class)
            ->set('status', 'inactive')
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 1
                && $groups->first()->is($ongoing));
    }

    public function test_new_group_code_continues_the_highest_current_year_sequence(): void
    {
        $admin = User::factory()->admin()->create();

        InternGroup::factory()->create(['code' => 'MAG-2026-001']);
        InternGroup::factory()->individual()->create(['code' => 'MAG-2026-IND-003']);
        InternGroup::factory()->create(['code' => 'MAG-2025-099']);
        InternGroup::factory()->create(['code' => 'MAG-2026-005'])->delete();

        Livewire::actingAs($admin)
            ->test(GroupForm::class)
            ->assertSet('code', 'MAG-2026-004');
    }

    public function test_group_detail_cannot_delete_a_group_with_members(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create();
        InternProfile::factory()->for(User::factory()->mahasiswa())->create([
            'intern_group_id' => $group->id,
        ]);

        Livewire::actingAs($admin)
            ->test(GroupShow::class, ['group' => $group])
            ->call('delete')
            ->assertDispatched('group-delete-failed');

        $this->assertModelExists($group);
    }

    public function test_completed_group_detail_shows_selesai_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $group = InternGroup::factory()->create(['end_date' => '2026-11-16']);

        Livewire::actingAs($admin)
            ->test(GroupShow::class, ['group' => $group])
            ->assertSee('Selesai')
            ->assertSee('Aktif');
    }
}
