<?php

namespace Tests\Feature;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncHolidaysCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set('services.holidays.primary_url', 'https://holidays.test/api/holidays');
        config()->set('services.holidays.fallback_url', 'https://fallback.test/data/libur-%d.json');
    }

    public function test_command_syncs_an_explicit_year(): void
    {
        Http::fake([
            'https://holidays.test/*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027 Masehi', 'type' => 'holiday'],
                ],
            ]),
        ]);

        $this->artisan('holidays:sync', ['year' => 2027])->assertSuccessful();

        $this->assertDatabaseHas('holidays', [
            'name' => 'Tahun Baru 2027 Masehi',
            'source' => Holiday::SOURCE_AUTO,
        ]);
        $this->assertTrue(Holiday::query()->whereDate('date', '2027-01-01')->exists());
    }

    public function test_command_syncs_current_and_next_year_by_default(): void
    {
        $this->travelTo(Carbon::parse('2026-11-19 10:00:00', 'Asia/Jakarta'));

        Http::fake(function (Request $request) {
            $year = (int) $request['year'];

            return Http::response([
                'success' => true,
                'data' => [
                    ['date' => "{$year}-01-01", 'name' => "Tahun Baru {$year} Masehi", 'type' => 'holiday'],
                ],
            ]);
        });

        $this->artisan('holidays:sync')->assertSuccessful();

        $this->assertTrue(Holiday::query()->whereDate('date', '2026-01-01')->exists());
        $this->assertTrue(Holiday::query()->whereDate('date', '2027-01-01')->exists());
    }

    public function test_command_rejects_years_outside_the_supported_range(): void
    {
        $this->artisan('holidays:sync', ['year' => 2019])->assertFailed();

        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_command_fails_when_an_explicit_year_cannot_be_synced(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'unavailable'], 500),
        ]);

        $this->artisan('holidays:sync', ['year' => 2027])->assertFailed();
    }

    public function test_default_sync_succeeds_when_only_next_year_fails(): void
    {
        $this->travelTo(Carbon::parse('2026-11-19 10:00:00', 'Asia/Jakarta'));

        Http::fake(function (Request $request) {
            $year = (int) $request['year'];

            if ($year === 2027) {
                return Http::response(['message' => 'unavailable'], 500);
            }

            return Http::response([
                'success' => true,
                'data' => [
                    ['date' => "{$year}-01-01", 'name' => "Tahun Baru {$year} Masehi", 'type' => 'holiday'],
                ],
            ]);
        });

        $this->artisan('holidays:sync')->assertSuccessful();

        $this->assertTrue(Holiday::query()->whereDate('date', '2026-01-01')->exists());
        $this->assertFalse(Holiday::query()->whereDate('date', '2027-01-01')->exists());
    }
}
