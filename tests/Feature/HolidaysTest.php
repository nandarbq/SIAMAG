<?php

namespace Tests\Feature;

use App\Livewire\Admin\Holidays\Index as HolidayIndex;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HolidaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
    }

    public function test_admin_can_create_a_holiday(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->call('openCreate')
            ->set('date', '2026-12-25')
            ->set('name', 'Hari Raya Natal')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('holidays', [
            'name' => 'Hari Raya Natal',
        ]);
    }

    public function test_admin_can_update_a_holiday(): void
    {
        $admin = User::factory()->admin()->create();
        $holiday = Holiday::factory()->create([
            'date' => '2026-12-25',
            'name' => 'Natal',
        ]);

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->call('openEdit', $holiday->id)
            ->assertSet('name', 'Natal')
            ->set('name', 'Hari Raya Natal')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Hari Raya Natal', $holiday->fresh()->name);
    }

    public function test_admin_can_delete_a_holiday(): void
    {
        $admin = User::factory()->admin()->create();
        $holiday = Holiday::factory()->create();

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->call('delete', $holiday->id);

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }
}
