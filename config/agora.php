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
    // appels support). Console Agora > Developer Hub > RESTful API :
    // Customer Key/Secret DIFFÉRENTS de app_id/app_certificate ci-dessus.
    //
    // ✅ Vendor 11 = "S3 compatible storage" — Backblaze B2 est compatible S3,
    // donc PAS BESOIN d'AWS : on utilise un bucket Backblaze DÉDIÉ (séparé de
    // celui des photos, pour garder les enregistrements privés — voir
    // 'backblaze' dans config/filesystems.php pour le bucket photos public).
    // Nécessite le endpoint S3 du bucket (ex: https://s3.us-west-004.backblazeb2.com).
    'cloud_recording' => [
        'customer_key'    => env('AGORA_CUSTOMER_KEY'),
        'customer_secret' => env('AGORA_CUSTOMER_SECRET'),
        'storage_vendor'  => (int) env('AGORA_RECORDING_STORAGE_VENDOR', 11), // 11 = S3-compatible (Backblaze)
        'storage_region'  => (int) env('AGORA_RECORDING_STORAGE_REGION', 0),  // ignoré par Agora pour vendor 11, mais requis par l'API — 0 est sûr
        'bucket'          => env('AGORA_RECORDING_BUCKET'),
        'access_key'      => env('AGORA_RECORDING_ACCESS_KEY'),
        'secret_key'      => env('AGORA_RECORDING_SECRET_KEY'),
        'endpoint'        => env('AGORA_RECORDING_ENDPOINT'), // requis pour vendor=11, ex: https://s3.us-west-004.backblazeb2.com
    ],
];
