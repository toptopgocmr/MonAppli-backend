<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | ⚠️ Ce fichier avait totalement disparu du dépôt (absent de git, absent
    | du disque) alors que .env définit SESSION_DRIVER=database et que la
    | migration `create_sessions_table` existe bien. Résultat : Laravel
    | n'avait plus AUCUNE config de session valide en production, ce qui
    | rendait les cookies de session peu fiables et provoquait les erreurs
    | "419 Page Expirée" (CSRF token mismatch) constatées sur /company/login
    | (et potentiellement /admin/login, /login). Restauré avec les valeurs
    | standard Laravel 11, alignées sur .env.example.
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'toptopgo'), '_').'_session'
    ),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
