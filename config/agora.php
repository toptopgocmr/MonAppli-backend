<?php

return [
    // Identifiants du projet Agora (console.agora.io > Project Management).
    'app_id'          => env('AGORA_APP_ID'),
    'app_certificate' => env('AGORA_APP_CERTIFICATE'),

    // Durée de validité du token RTC (secondes). 1h par défaut : largement
    // suffisant pour un appel voix, régénéré à chaque initiate()/answer().
    'token_ttl' => (int) env('AGORA_TOKEN_TTL', 3600),

    // ── Cloud Recording (enregistrement serveur des appels client↔chauffeur,
    // qui n'ont aucune jambe web pour enregistrer via MediaRecorder comme les
    // appels support). Console Agora > Project Management > RESTful API :
    // Customer Key/Secret DIFFÉRENTS de app_id/app_certificate ci-dessus.
    // Bucket S3 dédié recommandé (peut être un bucket AWS S3 séparé du
    // Backblaze déjà utilisé pour les photos — Agora Cloud Recording ne
    // supporte pas Backblaze nativement, vendor=1 = AWS S3 uniquement testé).
    'cloud_recording' => [
        'customer_key'    => env('AGORA_CUSTOMER_KEY'),
        'customer_secret' => env('AGORA_CUSTOMER_SECRET'),
        'storage_vendor'  => (int) env('AGORA_RECORDING_STORAGE_VENDOR', 1), // 1 = AWS S3
        'storage_region'  => (int) env('AGORA_RECORDING_STORAGE_REGION', 0), // 0 = us-east-1
        'bucket'          => env('AGORA_RECORDING_BUCKET'),
        'access_key'      => env('AGORA_RECORDING_ACCESS_KEY'),
        'secret_key'      => env('AGORA_RECORDING_SECRET_KEY'),
    ],
];
