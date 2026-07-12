<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // ✅ Backblaze B2 (compatible S3)
        'backblaze' => [
            'driver'                  => 's3',
            'key'                     => env('BACKBLAZE_KEY_ID'),
            'secret'                  => env('BACKBLAZE_APPLICATION_KEY'),
            'region'                  => env('BACKBLAZE_REGION', 'us-west-004'),
            'bucket'                  => env('BACKBLAZE_BUCKET'),
            'endpoint'                => env('BACKBLAZE_ENDPOINT'),
            // ✅ CORRIGÉ : format S3 (sans /file/) — ex: https://s3.us-west-004.backblazeb2.com/toptopgo2026
            'url'                     => env('BACKBLAZE_ENDPOINT') . '/' . env('BACKBLAZE_BUCKET'),
            'visibility'              => 'public',
            'throw'                   => false,
            'use_path_style_endpoint' => true,
        ],

        // ✅ Bucket AWS S3 dédié où Agora Cloud Recording dépose directement
        // les enregistrements des appels client↔chauffeur (mobile↔mobile,
        // voir AgoraCloudRecordingService). Mêmes identifiants que
        // config('agora.cloud_recording'), utilisés ici pour RELIRE les
        // fichiers (Storage::disk('agora_recordings')->response(...)) via la
        // route authentifiée admin, sans jamais exposer le bucket en public.
        'agora_recordings' => [
            'driver'     => 's3',
            'key'        => env('AGORA_RECORDING_ACCESS_KEY'),
            'secret'     => env('AGORA_RECORDING_SECRET_KEY'),
            'region'     => env('AGORA_RECORDING_S3_REGION', 'us-east-1'),
            'bucket'     => env('AGORA_RECORDING_BUCKET'),
            'visibility' => 'private',
            'throw'      => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];