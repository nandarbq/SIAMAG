<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Services\HolidaySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HolidaySyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set('services.holidays.primary_url', 'https://holidays.test/api/holidays');
        config()->set('services.holidays.fallback_url', 'https://fallback.test/data/libur-%d.json');
    }

    public function test_sync_creates_auto_holidays_from_primary_source(): void
    {
        Http::fake([
            'https://holidays.test/*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027 Masehi', 'type' => 'holiday'],
                    ['date' => '2027-02-05', 'name' => 'Tahun Baru Imlek 2578 Kongzili', 'type' => 'leave'],
                ],
            ]),
        ]);

        $result = app(HolidaySyncService::class)->syncYear(2027);

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame('primary', $result['source']);
        $this->assertDatabaseHas('holidays', [
            'name' => 'Tahun Baru 2027 Masehi',
            'source' => Holiday::SOURCE_AUTO,
        ]);
        $this->assertDatabaseHas('holidays', [
            'name' => 'Tahun Baru Imlek 2578 Kongzili',
            'source' => Holiday::SOURCE_AUTO,
        ]);
        $this->assertTrue(Holiday::query()->whereDate('date', '2027-01-01')->exists());
        $this->assertTrue(Holiday::query()->whereDate('date', '2027-02-05')->exists());
    }

    public function test_sync_uses_fallback_source_when_primary_fails(): void
    {
        Http::fake([
            'https://holidays.test/*' => Http::response(['message' => 'unavailable'], 500),
            'https://fallback.test/data/libur-2027.json' => Http::response([
                'tahun' => 2027,
                'hari' => [
                    ['tanggal' => '2027-01-01', 'nama' => 'Tahun Baru 2027 Masehi', 'jenis' => 'libur_nasional'],
                    ['tanggal' => '2027-02-05', 'nama' => 'Tahun Baru Imlek 2578 Kongzili', 'jenis' => 'cuti_bersama'],
                ],
            ]),
        ]);

        $result = app(HolidaySyncService::class)->syncYear(2027);

        $this->assertSame(2, $result['created']);
        $this->assertSame('fallback', $result['source']);
        $this->assertDatabaseHas('holidays', [
            'name' => 'Tahun Baru 2027 Masehi',
            'source' => Holiday::SOURCE_AUTO,
        ]);
        $this->assertTrue(Holiday::query()->whereDate('date', '2027-01-01')->exists());
    }

    public function test_sync_preserves_manual_holidays(): void
    {
        $holiday = Holiday::factory()->create([
            'date' => '2027-01-01',
            'name' => 'Libur Manual',
            'source' => Holiday::SOURCE_MANUAL,
        ]);

        Http::fake([
            'https://holidays.test/*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027 Masehi', 'type' => 'holiday'],
                ],
            ]),
        ]);

        $result = app(HolidaySyncService::class)->syncYear(2027);

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('Libur Manual', $holiday->fresh()->name);
        $this->assertSame(Holiday::SOURCE_MANUAL, $holiday->fresh()->source);
    }

    public function test_force_sync_overwrites_manual_holidays(): void
    {
        $holiday = Holiday::factory()->create([
            'date' => '2027-01-01',
            'name' => 'Libur Manual',
            'source' => Holiday::SOURCE_MANUAL,
        ]);

        Http::fake([
            'https://holidays.test/*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027 Masehi', 'type' => 'holiday'],
                ],
            ]),
        ]);

        $result = app(HolidaySyncService::class)->syncYear(2027, force: true);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame('Tahun Baru 2027 Masehi', $holiday->fresh()->name);
        $this->assertSame(Holiday::SOURCE_AUTO, $holiday->fresh()->source);
    }

    public function test_sync_ignores_invalid_dates_and_entries_from_other_years(): void
    {
        Http::fake([
            'https://holidays.test/*' => Http::response([
                'success' => true,
                'data' => [
                    ['date' => '2027-01-01', 'name' => 'Tahun Baru 2027 Masehi', 'type' => 'holiday'],
                    ['date' => '2028-01-01', 'name' => 'Tahun Baru 2028 Masehi', 'type' => 'holiday'],
                    ['date' => '2027-02-30', 'name' => 'Tanggal Tidak Valid', 'type' => 'holiday'],
                    ['date' => '2027-03-01', 'name' => '  ', 'type' => 'holiday'],
                ],
            ]),
        ]);

        $result = app(HolidaySyncService::class)->syncYear(2027);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['total']);
        $this->assertDatabaseMissing('holidays', ['name' => 'Tahun Baru 2028 Masehi']);
        $this->assertDatabaseMissing('holidays', ['name' => 'Tanggal Tidak Valid']);
    }
}
