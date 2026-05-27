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

    /*
    | Paramètres Congo Brazzaville
    */
    'country_code' => '242',
    'currency'     => 'XAF',

    /*
    | Réseaux pris en charge (valeurs envoyées à l'API FLW)
    */
    'networks' => [
        'mtn'    => 'MTN',
        'airtel' => 'AIRTEL',
    ],
];
