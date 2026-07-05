<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
            Route::middleware('web')
                ->group(base_path('routes/company.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum stateful API
        $middleware->statefulApi();

        // Exclure les routes non-browser du CSRF
        $middleware->validateCsrfTokens(except: [
            'admin/login',
            'api/*',
            'webhooks/*',
        ]);

        // Alias middleware custom
        $middleware->alias([
            'role.permission'   => \App\Http\Middleware\RolePermissionMiddleware::class,
            'admin.session'     => \App\Http\Middleware\AdminSessionMiddleware::class,
            'company'           => \App\Http\Middleware\CompanyMiddleware::class,
            'company.permission'=> \App\Http\Middleware\CompanyPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();