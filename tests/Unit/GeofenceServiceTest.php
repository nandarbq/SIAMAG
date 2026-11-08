<?php

namespace Tests\Unit;

use App\Services\GeofenceService;
use PHPUnit\Framework\TestCase;

class GeofenceServiceTest extends TestCase
{
    public function test_same_coordinates_return_zero_distance(): void
    {
        $distance = (new GeofenceService)->distanceInMeters(-8.2192, 114.3692, -8.2192, 114.3692);

        $this->assertSame(0, $distance);
    }

    public function test_distance_is_calculated_in_meters(): void
    {
        $distance = (new GeofenceService)->distanceInMeters(-8.2192, 114.3692, -8.2202, 114.3692);

        $this->assertGreaterThanOrEqual(110, $distance);
        $this->assertLessThanOrEqual(112, $distance);
    }

    public function test_nearby_point_falls_within_default_radius(): void
    {
        $distance = (new GeofenceService)->distanceInMeters(-8.2192, 114.3692, -8.2193, 114.3693);

        $this->assertLessThan(100, $distance);
    }
}
