<?php

return [
    // Identifiants du projet Agora (console.agora.io > Project Management).
    'app_id'          => env('AGORA_APP_ID'),
    'app_certificate' => env('AGORA_APP_CERTIFICATE'),

    // Durée de validité du token RTC (secondes). 1h par défaut : largement
    // suffisant pour un appel voix, régénéré à chaque initiate()/answer().
    'token_ttl' => (int) env('AGORA_TOKEN_TTL', 3600),
];
