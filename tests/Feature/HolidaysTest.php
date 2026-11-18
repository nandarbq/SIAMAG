<?php

namespace Tests\Feature;

use App\Livewire\Admin\Holidays\Index as HolidayIndex;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HolidaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));

        Http::preventStrayRequests();

        config()->set('services.holidays.primary_url', 'https://holidays.test/api/holidays');
        config()->set('services.holidays.fallback_url', 'https://fallback.test/data/libur-%d.json');
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
            'source' => Holiday::SOURCE_MANUAL,
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
        $this->assertSame(Holiday::SOURCE_MANUAL, $holiday->fresh()->source);
    }

    public function test_editing_an_auto_holiday_marks_it_as_manual(): void
    {
        $admin = User::factory()->admin()->create();
        $holiday = Holiday::factory()->create([
            'date' => '2026-12-25',
            'name' => 'Natal',
            'source' => Holiday::SOURCE_AUTO,
        ]);

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->call('openEdit', $holiday->id)
            ->set('name', 'Hari Raya Natal')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Holiday::SOURCE_MANUAL, $holiday->fresh()->source);
    }

    public function test_admin_can_sync_holidays_for_the_selected_year(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            'https://holidays.test/*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027 Masehi', 'type' => 'holiday'],
                ],
            ]),
        ]);

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->set('year', '2027')
            ->call('sync')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('holidays', [
            'name' => 'Tahun Baru 2027 Masehi',
            'source' => Holiday::SOURCE_AUTO,
        ]);
        $this->assertTrue(Holiday::query()->whereDate('date', '2027-01-01')->exists());
    }

    public function test_admin_sync_rejects_invalid_years(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->set('year', '2019')
            ->call('sync')
            ->assertHasErrors(['year']);
    }

    public function test_duplicate_holiday_dates_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Holiday::factory()->create(['date' => '2026-12-25']);

        Livewire::actingAs($admin)
            ->test(HolidayIndex::class)
            ->set('date', '2026-12-25')
            ->set('name', 'Natal')
            ->call('save')
            ->assertHasErrors(['date']);
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
