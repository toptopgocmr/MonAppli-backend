<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Fare
    |--------------------------------------------------------------------------
    |
    | The minimum charge for any ride in XAF
    |
    */

    'base_fare' => env('RIDE_BASE_FARE', 500),

    /*
    |--------------------------------------------------------------------------
    | Minimum Fare
    |--------------------------------------------------------------------------
    |
    | The minimum total price for any ride in XAF
    |
    */

    'minimum_fare' => env('RIDE_MINIMUM_FARE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Price Per Kilometer
    |--------------------------------------------------------------------------
    |
    | The price per kilometer for each vehicle type in XAF
    |
    */

    'price_per_km' => [
        'standard' => env('RIDE_PRICE_PER_KM_STANDARD', 150),
        'comfort' => env('RIDE_PRICE_PER_KM_COMFORT', 200),
        'premium' => env('RIDE_PRICE_PER_KM_PREMIUM', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cancellation Policy
    |--------------------------------------------------------------------------
    |
    | Cancellation fees and time limits
    |
    */

    'cancellation' => [
        // Time in minutes after which cancellation fee applies
        'free_cancellation_minutes' => env('FREE_CANCELLATION_MINUTES', 5),

        // Cancellation fee percentage of ride price
        'fee_percentage' => env('CANCELLATION_FEE_PERCENTAGE', 20),

        // Maximum cancellations per day before penalty
        'max_daily_cancellations' => env('MAX_DAILY_CANCELLATIONS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Radius
    |--------------------------------------------------------------------------
    |
    | Default radius in kilometers to search for drivers
    |
    */

    'search_radius_km' => env('DRIVER_SEARCH_RADIUS_KM', 10),

    /*
    |--------------------------------------------------------------------------
    | Wait Time
    |--------------------------------------------------------------------------
    |
    | Maximum time to wait for a driver in minutes
    |
    */

    'max_wait_time_minutes' => env('MAX_WAIT_TIME_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Surge Pricing
    |--------------------------------------------------------------------------
    |
    | Dynamic pricing multipliers during peak hours
    |
    */

    'surge' => [
        'enabled' => env('SURGE_PRICING_ENABLED', false),
        'max_multiplier' => env('SURGE_MAX_MULTIPLIER', 2.0),

        // Threshold: minimum available drivers before surge kicks in
        'threshold_drivers' => env('SURGE_THRESHOLD_DRIVERS', 5),
    ],

];
