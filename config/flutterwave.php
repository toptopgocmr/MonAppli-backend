<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flutterwave – ToptopGo
    |--------------------------------------------------------------------------
    | Clés récupérées sur https://app.flutterwave.com → Settings → API Keys
    |
    | FLW_ENV : 'sandbox' pour tests | 'production' pour le live
    */

    'secret_key'  => env('FLW_SECRET_KEY'),
    'public_key'  => env('FLW_PUBLIC_KEY'),

    /*
    | Secret hash configuré dans Dashboard FLW → Settings → Webhooks
    | Utilisé pour vérifier la signature HMAC-SHA256 des webhooks entrants.
    */
    'secret_hash' => env('FLW_SECRET_HASH'),

    'env' => env('FLW_ENV', 'sandbox'),

    'base_url' => [
        'sandbox'    => 'https://developersandbox-api.flutterwave.com',
        'production' => 'https://api.flutterwave.com',
    ],

    'currency' => env('FLW_CURRENCY', 'XAF'),

    'networks' => [
        'mtn'    => 'MTN',
        'airtel' => 'AIRTEL',
    ],
];
