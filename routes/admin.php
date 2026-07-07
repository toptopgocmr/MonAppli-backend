<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Http\Controllers\Admin\AdminUserSupportController;
use App\Http\Controllers\Admin\AdminDriverSupportController;
use App\Http\Controllers\Admin\AdminCallController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\CommissionRateController;
use App\Http\Controllers\Admin\PaymentPartnerController;
use App\Http\Controllers\Admin\CompanyWithdrawalController;
use App\Http\Controllers\Admin\SosAlertController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\VehicleTypeController;
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
        Route::get('geolocation', [MapController::class, 'index'])->name('geolocation.index');
        Route::get('geolocation/trips', [MapController::class, 'trips'])->name('geolocation.trips');

        /*
        |--------------------------------------------------------------------------
        | VÉRIFICATION KYC
        |--------------------------------------------------------------------------
        */
        Route::prefix('kyc')->name('kyc.')->group(function () {
            Route::get('/',          [KycController::class, 'index'])->name('index');
            Route::get('/{driver}',  [KycController::class, 'review'])->name('review');
            Route::post('/{driver}/approve', [KycController::class, 'approve'])->name('approve');
            Route::post('/{driver}/reject',  [KycController::class, 'reject'])->name('reject');
        });

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
        | APPELS VOIX IN-APP — SUPPORT (client, chauffeur, société)
        |--------------------------------------------------------------------------
        */
        Route::post('calls/initiate',       [AdminCallController::class, 'initiate'])->name('calls.initiate');
        Route::post('calls/{callId}/answer', [AdminCallController::class, 'answer'])->name('calls.answer');
        Route::post('calls/{callId}/end',    [AdminCallController::class, 'end'])->name('calls.end');
        Route::post('calls/{callId}/missed', [AdminCallController::class, 'missed'])->name('calls.missed');
        Route::get('calls/{callId}/token',   [AdminCallController::class, 'token'])->name('calls.token');

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
        /*
        |--------------------------------------------------------------------------
        | SOCIÉTÉS
        |--------------------------------------------------------------------------
        */
        Route::prefix('companies')->name('companies.')->group(function () {
            Route::get('/',                                  [CompanyController::class, 'index'])->name('index');
            Route::get('/create',                            [CompanyController::class, 'create'])->name('create');
            Route::post('/',                                 [CompanyController::class, 'store'])->name('store');
            Route::get('/{company}',                         [CompanyController::class, 'show'])->name('show');
            Route::get('/{company}/edit',                    [CompanyController::class, 'edit'])->name('edit');
            Route::put('/{company}',                         [CompanyController::class, 'update'])->name('update');
            Route::delete('/{company}',                      [CompanyController::class, 'destroy'])->name('destroy');
            Route::post('/{company}/suspend',                [CompanyController::class, 'suspend'])->name('suspend');
            Route::post('/{company}/activate',               [CompanyController::class, 'activate'])->name('activate');
            Route::post('/{company}/assign-driver',          [CompanyController::class, 'assignDriver'])->name('assign-driver');
            Route::delete('/{company}/drivers/{driver}',     [CompanyController::class, 'removeDriver'])->name('remove-driver');
        });

        Route::get('payments', [PaymentPartnerController::class, 'index'])->name('payments.index');
        Route::get('payments/export', [PaymentPartnerController::class, 'export'])->name('payments.export');
        Route::get('payments/withdrawals', [PaymentPartnerController::class, 'withdrawalsIndex'])->name('payments.withdrawals');
        Route::get('payments/withdrawals/export', [PaymentPartnerController::class, 'exportWithdrawals'])->name('payments.withdrawals.export');
        Route::post('payments/withdrawals/{withdrawal}/approve', [PaymentPartnerController::class, 'approveWithdrawal'])->name('payments.approve-withdrawal');
        Route::post('payments/withdrawals/{withdrawal}/reject',  [PaymentPartnerController::class, 'rejectWithdrawal'])->name('payments.reject-withdrawal');

        Route::get('company-withdrawals', [CompanyWithdrawalController::class, 'index'])->name('company-withdrawals.index');
        Route::post('company-withdrawals/{withdrawal}/approve', [CompanyWithdrawalController::class, 'approve'])->name('company-withdrawals.approve');
        Route::post('company-withdrawals/{withdrawal}/reject',  [CompanyWithdrawalController::class, 'reject'])->name('company-withdrawals.reject');

        Route::prefix('vehicle-types')->name('vehicle-types.')->group(function () {
            Route::get('/',                  [VehicleTypeController::class, 'index'])->name('index');
            Route::post('/',                 [VehicleTypeController::class, 'store'])->name('store');
            Route::post('/{vehicleType}/toggle', [VehicleTypeController::class, 'toggle'])->name('toggle');
            Route::delete('/{vehicleType}',  [VehicleTypeController::class, 'destroy'])->name('destroy');
        });

    });

});
