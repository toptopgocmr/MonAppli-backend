<?php

return [

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'admin' => [
            'driver'   => 'session',
            'provider' => 'admins',
        ],
        'company' => [
            'driver'   => 'session',
            'provider' => 'companies',
        ],
        'company_agent' => [
            'driver'   => 'session',
            'provider' => 'company_agents',
        ],
        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => null,
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\AdminUser::class,
        ],
        'drivers' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Driver::class,
        ],
        'companies' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Company::class,
        ],
        'company_agents' => [
            'driver' => 'eloquent',
            'model'  => App\Models\CompanyAgent::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
