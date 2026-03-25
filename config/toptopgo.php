<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission TopTopGo
    |--------------------------------------------------------------------------
    | Pourcentage de commission sur chaque course (ex: 0.20 = 20%)
    */
    'commission_rate' => env('TOPTOPGO_COMMISSION_RATE', 0.20),

    /*
    |--------------------------------------------------------------------------
    | Tarification par type de véhicule
    |--------------------------------------------------------------------------
    | 'base' = montant fixe de départ (XAF)
    | 'per_km' = montant par kilomètre (XAF)
    */
    'pricing' => [
        'Standard' => ['base' => 500,  'per_km' => 300],
        'Confort'  => ['base' => 800,  'per_km' => 450],
        'Van'      => ['base' => 1000, 'per_km' => 600],
        'PMR'      => ['base' => 1000, 'per_km' => 500],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrait minimum
    |--------------------------------------------------------------------------
    */
    'min_withdrawal' => env('TOPTOPGO_MIN_WITHDRAWAL', 500),

    /*
    |--------------------------------------------------------------------------
    | Méthodes de paiement disponibles
    |--------------------------------------------------------------------------
    */
    'payment_methods' => [
        'mobile_money' => ['mtn', 'orange', 'airtel', 'moov'],
        'card'         => ['visa', 'mastercard'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pays disponibles (Phase 1 - Afrique Centrale)
    |--------------------------------------------------------------------------
    */
    'countries' => [
        'CG' => 'Congo Brazzaville',
        'CM' => 'Cameroun',
        'GA' => 'Gabon',
        'GQ' => 'Guinée Équatoriale',
        'CF' => 'République Centrafricaine',
        'TD' => 'Tchad',
    ],

    /*
    |--------------------------------------------------------------------------
    | GPS — fréquence de mise à jour (secondes)
    |--------------------------------------------------------------------------
    */
    'gps_interval_seconds' => env('TOPTOPGO_GPS_INTERVAL', 10),

    /*
    |--------------------------------------------------------------------------
    | Rétention des données (années)
    |--------------------------------------------------------------------------
    */
    'data_retention_years' => 10,
];
