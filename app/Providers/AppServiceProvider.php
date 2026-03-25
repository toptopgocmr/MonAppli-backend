<?php

namespace App\Providers;

use App\Services\Payment\PaymentService;
use App\Services\Payment\PeexService;
use App\Services\Payment\MtnMomoService;
use App\Services\Payment\AirtelMoneyService;
use App\Services\Payment\StripeService;

use App\Models\Course;
use App\Observers\CourseObserver;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PeexService::class);
        $this->app->singleton(MtnMomoService::class);
        $this->app->singleton(AirtelMoneyService::class);
        $this->app->singleton(StripeService::class);
        $this->app->singleton(PaymentService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Course::observe(CourseObserver::class);

        // ✅ Force HTTPS en production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ✅ FIX: crée le lien symbolique storage si absent (résout les 404 sur photos)
        if (!file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }
    }
}