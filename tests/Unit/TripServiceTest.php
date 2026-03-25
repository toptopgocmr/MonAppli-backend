<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TripService;

class TripServiceTest extends TestCase
{
    private TripService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TripService();
    }

    public function test_distance_calculation_is_correct(): void
    {
        // Brazzaville -> Pointe-Noire ~ 500 km
        $distance = $this->service->calculateDistance(
            -4.2634, 15.2429,  // Brazzaville
            -4.7763, 11.8635   // Pointe-Noire
        );

        $this->assertGreaterThan(400, $distance);
        $this->assertLessThan(600, $distance);
    }

    public function test_price_estimation_standard(): void
    {
        $price = $this->service->estimatePrice(10.0, 'Standard');
        // base 500 + 10 * 300 = 3500
        $this->assertEquals(3500, $price);
    }

    public function test_price_estimation_confort(): void
    {
        $price = $this->service->estimatePrice(5.0, 'Confort');
        // base 800 + 5 * 450 = 3050
        $this->assertEquals(3050, $price);
    }

    public function test_commission_rate_is_20_percent(): void
    {
        $amount     = 1000;
        $commission = $amount * TripService::COMMISSION_RATE;
        $this->assertEquals(200, $commission);
    }
}
