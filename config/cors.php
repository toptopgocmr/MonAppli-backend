<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    // ✅ Autorise TOUT — mobile (APK/iOS), Flutter Web, Railway, Postman
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // ✅ DOIT être false quand allowed_origins = ['*']
    // Si true + '*' → erreur CORS navigateur
    'supports_credentials' => false,

];