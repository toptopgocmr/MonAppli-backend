<?php

namespace App\Providers;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Events\PayoutCompleted;
use App\Events\RideCreated;
use App\Events\RideAccepted;
use App\Events\RideStarted;
use App\Events\RideCompleted;
use App\Events\RideCancelled;
use App\Listeners\SendPaymentNotification;
use App\Listeners\ProcessCompletedPayment;
use App\Listeners\NotifyDriversAboutNewRide;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        PaymentCompleted::class => [
            SendPaymentNotification::class,
            ProcessCompletedPayment::class,
        ],
        PaymentFailed::class => [
            SendPaymentNotification::class,
        ],
        PayoutCompleted::class => [
            SendPaymentNotification::class,
        ],
        RideCreated::class => [
            NotifyDriversAboutNewRide::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
