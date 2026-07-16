<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | ⚠️ Fichier absent du dépôt (voir config/session.php pour le contexte).
    | Restauré avec les clés standard Laravel 11. Les identifiants Peex,
    | MTN/Orange/Airtel Money, Agora, Flutterwave etc. vivent dans leurs
    | propres fichiers dédiés (config/payments.php, config/agora.php,
    | config/flutterwave.php, config/mobile_money.php) — inchangés.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
