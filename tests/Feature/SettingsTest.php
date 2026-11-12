<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_admin_can_update_office_and_attendance_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->set('office_name', 'BMKG Stasiun Geofisika Banyuwangi')
            ->set('office_address', 'Jalan Argopuro No. 1')
            ->set('office_lat', '-8.2180000')
            ->set('office_lng', '114.3700000')
            ->set('radius_m', '200')
            ->set('max_accuracy_m', '80')
            ->set('work_start', '08:00')
            ->set('work_end', '16:30')
            ->set('late_tolerance_min', '10')
            ->set('logbook_export_lead_days', '10')
            ->set('require_selfie', false)
            ->set('require_geofence', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('BMKG Stasiun Geofisika Banyuwangi', Setting::get('office_name'));
        $this->assertSame('-8.2180000', Setting::get('office_lat'));
        $this->assertSame('114.3700000', Setting::get('office_lng'));
        $this->assertSame('200', Setting::get('radius_m'));
        $this->assertSame('80', Setting::get('max_accuracy_m'));
        $this->assertSame('08:00', Setting::get('work_start'));
        $this->assertSame('10', Setting::get('logbook_export_lead_days'));
        $this->assertFalse(Setting::getBool('require_selfie'));
        $this->assertTrue(Setting::getBool('require_geofence'));
    }

    public function test_admin_can_disable_geofence_validation(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->set('require_geofence', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(Setting::getBool('require_geofence'));
    }

    public function test_work_end_must_be_after_work_start(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->set('work_start', '16:00')
            ->set('work_end', '08:00')
            ->call('save')
            ->assertHasErrors(['work_end']);
    }

    public function test_logbook_export_lead_days_must_be_between_one_and_thirty(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->set('logbook_export_lead_days', '0')
            ->call('save')
            ->assertHasErrors(['logbook_export_lead_days']);

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->set('logbook_export_lead_days', '31')
            ->call('save')
            ->assertHasErrors(['logbook_export_lead_days']);
    }

    public function test_admin_can_load_existing_settings(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::put('office_name', 'BMKG Banyuwangi');
        Setting::put('radius_m', '250');

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->assertSet('office_name', 'BMKG Banyuwangi')
            ->assertSet('radius_m', '250');
    }
}
