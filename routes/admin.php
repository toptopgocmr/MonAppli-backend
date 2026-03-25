<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Http\Controllers\Admin\AdminUserSupportController;
use App\Http\Controllers\Admin\AdminDriverSupportController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\CommissionRateController;
use App\Http\Controllers\Admin\PaymentPartnerController;
use App\Http\Controllers\Admin\SosAlertController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\TripController; // ✅ Ajouté
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (Blade / Web uniquement)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | LOGIN ADMIN
    |--------------------------------------------------------------------------
    */

    Route::get('login', function () {
        if (session('admin_id')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    })->name('login');

    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');

    /*
    |--------------------------------------------------------------------------
    | ROUTES PROTEGEES
    |--------------------------------------------------------------------------
    */

    Route::middleware('admin.session')->group(function () {

        // DASHBOARD
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ✅ API temps réel positions chauffeurs
        Route::get('drivers/live', [DashboardController::class, 'liveDrivers'])->name('drivers.live');

        // LOGOUT
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        /*
        |--------------------------------------------------------------------------
        | GÉOLOCALISATION LIVE (carte)
        |--------------------------------------------------------------------------
        */
        Route::get('geolocation', [MapController::class, 'index'])->name('geolocation');
        Route::get('geolocation/trips', [MapController::class, 'trips'])->name('geolocation.trips');

        /*
        |--------------------------------------------------------------------------
        | TRAJETS & COURSES ✅ Ajouté
        |--------------------------------------------------------------------------
        */
        Route::prefix('trips')->name('trips.')->group(function () {
            Route::get('/',            [TripController::class, 'index'])->name('index');
            Route::get('/{id}/detail', [TripController::class, 'detail'])->name('detail');
            Route::get('/{id}',        [TripController::class, 'show'])->name('show');
        });

        /*
        |--------------------------------------------------------------------------
        | GESTION DES PROFILS ADMINS
        |--------------------------------------------------------------------------
        */
        Route::get('profiles', [AdminProfileController::class, 'index'])->name('profiles.index');
        Route::get('profiles/create', [AdminProfileController::class, 'create'])->name('profiles.create');
        Route::post('profiles', [AdminProfileController::class, 'store'])->name('profiles.store');
        Route::get('profiles/{id}', [AdminProfileController::class, 'show'])->name('profiles.show');
        Route::get('profiles/{id}/edit', [AdminProfileController::class, 'edit'])->name('profiles.edit');
        Route::put('profiles/{id}', [AdminProfileController::class, 'update'])->name('profiles.update');
        Route::post('profiles/{id}/block', [AdminProfileController::class, 'block'])->name('profiles.block');
        Route::post('profiles/{id}/activate', [AdminProfileController::class, 'activate'])->name('profiles.activate');
        Route::delete('profiles/{id}', [AdminProfileController::class, 'destroy'])->name('profiles.destroy');

        /*
        |--------------------------------------------------------------------------
        | GESTION DES CHAUFFEURS
        |--------------------------------------------------------------------------
        */
        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('drivers/create', [DriverController::class, 'create'])->name('drivers.create');
        Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('drivers/{id}', [DriverController::class, 'show'])->name('drivers.show');
        Route::get('drivers/{id}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
        Route::put('drivers/{id}', [DriverController::class, 'update'])->name('drivers.update');
        Route::post('drivers/{id}/approve', [DriverController::class, 'approve'])->name('drivers.approve');
        Route::post('drivers/{id}/reject', [DriverController::class, 'reject'])->name('drivers.reject');
        Route::post('drivers/{id}/suspend', [DriverController::class, 'suspend'])->name('drivers.suspend');
        Route::post('drivers/{id}/activate', [DriverController::class, 'activate'])->name('drivers.activate');
        Route::delete('drivers/{id}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        /*
        |--------------------------------------------------------------------------
        | GESTION DES CLIENTS
        |--------------------------------------------------------------------------
        */
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');
        Route::post('users/{id}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('users/{id}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        /*
        |--------------------------------------------------------------------------
        | MESSAGES USERS ↔ CHAUFFEURS
        |--------------------------------------------------------------------------
        */
        Route::get('messages/users-drivers', [AdminMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/users-drivers/{trip}', [AdminMessageController::class, 'show'])->name('messages.show');

        /*
        |--------------------------------------------------------------------------
        | SUPPORT ADMIN ↔ UTILISATEURS
        |--------------------------------------------------------------------------
        */
        Route::get('support/users', [AdminUserSupportController::class, 'index'])->name('support.users.index');
        Route::get('support/users/{user}', [AdminUserSupportController::class, 'show'])->name('support.users.show');
        Route::post('support/users/{user}/send', [AdminUserSupportController::class, 'send'])->name('support.users.send');

        /*
        |--------------------------------------------------------------------------
        | SUPPORT ADMIN ↔ CHAUFFEURS
        |--------------------------------------------------------------------------
        */
        Route::get('support/drivers', [AdminDriverSupportController::class, 'index'])->name('support.drivers.index');
        Route::get('support/drivers/{driver}', [AdminDriverSupportController::class, 'show'])->name('support.drivers.show');
        Route::post('support/drivers/{driver}/send', [AdminDriverSupportController::class, 'send'])->name('support.drivers.send');

        /*
        |--------------------------------------------------------------------------
        | SOS ALERTES
        |--------------------------------------------------------------------------
        */
        Route::prefix('sos')->name('sos.')->group(function () {
            Route::get('/live',        [SosAlertController::class, 'live'])->name('live');
            Route::get('/',            [SosAlertController::class, 'index'])->name('index');
            Route::post('/treat-all',  [SosAlertController::class, 'treatAll'])->name('treat-all');
            Route::get('/{id}',        [SosAlertController::class, 'show'])->name('show');
            Route::post('/{id}/treat', [SosAlertController::class, 'treat'])->name('treat');
            Route::delete('/{id}',     [SosAlertController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | REVENUS
        |--------------------------------------------------------------------------
        */
        Route::prefix('revenus')->name('revenus.')->group(function () {
            Route::get('/',            [RevenueController::class, 'index'])->name('index');
            Route::get('/stats',       [RevenueController::class, 'stats'])->name('stats');
            Route::get('/by-country',  [RevenueController::class, 'byCountry'])->name('by-country');
            Route::get('/by-city',     [RevenueController::class, 'byCity'])->name('by-city');
            Route::get('/by-driver',   [RevenueController::class, 'byDriver'])->name('by-driver');
            Route::get('/by-client',   [RevenueController::class, 'byClient'])->name('by-client');
            Route::get('/export',      [RevenueController::class, 'export'])->name('export');
        });

        /*
        |--------------------------------------------------------------------------
        | TAUX DE COMMISSION
        |--------------------------------------------------------------------------
        */
        Route::prefix('commission-rates')->name('commission-rates.')->group(function () {
            Route::get('/export',              [CommissionRateController::class, 'export'])->name('export');
            Route::get('/',                    [CommissionRateController::class, 'index'])->name('index');
            Route::post('/',                   [CommissionRateController::class, 'store'])->name('store');
            Route::put('/{commissionRate}',    [CommissionRateController::class, 'update'])->name('update');
            Route::delete('/{commissionRate}', [CommissionRateController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | PARTENAIRES PAYEURS
        |--------------------------------------------------------------------------
        */
        Route::get('payments', [PaymentPartnerController::class, 'index'])->name('payments.index');
        Route::get('payments/export', [PaymentPartnerController::class, 'export'])->name('payments.export');
        Route::post('payments/withdrawals/{withdrawal}/approve', [PaymentPartnerController::class, 'approveWithdrawal'])->name('payments.approve-withdrawal');
        Route::post('payments/withdrawals/{withdrawal}/reject',  [PaymentPartnerController::class, 'rejectWithdrawal'])->name('payments.reject-withdrawal');

    });

});