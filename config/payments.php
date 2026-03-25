<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment provider for the application.
    | Supported: "peex", "mtn_momo", "airtel_money", "stripe"
    |
    */

    'default' => env('PAYMENT_DEFAULT_PROVIDER', 'peex'),

    /*
    |--------------------------------------------------------------------------
    | Peex Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Documentation: https://peex-api-docs.peexit.com/
    | Primary payment gateway for Congo Brazzaville
    |
    */

    'peex' => [
        'base_url' => env('PEEX_BASE_URL', 'https://sandbox.peexit.com/api/v1/'),
        'production_url' => env('PEEX_PRODUCTION_URL', 'https://api.peexit.com/api/v1/'),
        'secret_key' => env('PEEX_SECRET_KEY'),
        'webhook_secret' => env('PEEX_WEBHOOK_SECRET'),
        'sandbox' => env('PEEX_SANDBOX', true),
        'currency' => 'XAF',
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | MTN Mobile Money Configuration
    |--------------------------------------------------------------------------
    |
    | MTN MoMo API integration for mobile money payments
    |
    */

    'mtn_momo' => [
        'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'api_user' => env('MTN_MOMO_API_USER'),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'environment' => env('MTN_MOMO_ENVIRONMENT', 'sandbox'),
        'currency' => 'XAF',
        'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Airtel Money Configuration
    |--------------------------------------------------------------------------
    |
    | Airtel Money API integration for mobile money payments
    |
    */

    'airtel_money' => [
        'base_url' => env('AIRTEL_MONEY_BASE_URL', 'https://openapiuat.airtel.africa'),
        'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
        'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
        'environment' => env('AIRTEL_MONEY_ENVIRONMENT', 'sandbox'),
        'currency' => 'XAF',
        'country' => 'CG',
        'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Stripe payment gateway for international card payments
    |
    */

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => 'XAF',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Fees Configuration
    |--------------------------------------------------------------------------
    |
    | Configure commission rates and fees for the platform
    |
    */

    'fees' => [
        'platform_commission' => env('PLATFORM_COMMISSION_PERCENT', 15), // 15%
        'minimum_commission' => env('MINIMUM_COMMISSION', 100), // 100 XAF
        'escrow_hold_hours' => env('ESCROW_HOLD_HOURS', 24), // Hold funds for 24h after ride
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Providers by Country
    |--------------------------------------------------------------------------
    |
    | Define available payment providers for each country
    |
    */

    'providers_by_country' => [
        'CG' => ['peex', 'mtn_momo', 'airtel_money'], // Congo Brazzaville
        'CD' => ['peex', 'airtel_money'], // Congo Kinshasa
        'GA' => ['peex', 'airtel_money'], // Gabon
        'CM' => ['peex', 'mtn_momo'], // Cameroon
        'INTERNATIONAL' => ['stripe'], // International
    ],

];
