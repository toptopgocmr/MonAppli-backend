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