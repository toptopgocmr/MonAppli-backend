<?php

use App\Http\Controllers\Company\CompanyAuthController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DriverController;
use App\Http\Controllers\Company\VehicleController;
use App\Http\Controllers\Company\ReservationController;
use App\Http\Controllers\Company\RevenueController;
use App\Http\Controllers\Company\ItineraryController;
use App\Http\Controllers\Company\CompanyMessageController;
use App\Http\Controllers\Company\CompanyCallController;
use App\Http\Controllers\Company\ScheduleController;
use App\Http\Controllers\Company\PricingGridController;
use App\Http\Controllers\Company\WithdrawalController;
use App\Http\Controllers\Company\AgentController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')->name('company.')->group(function () {

    // ── LOGIN ────────────────────────────────────────────────────
    Route::get('/login',  [CompanyAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CompanyAuthController::class, 'login'])->name('login.post');

    // ── PANEL PROTÉGÉ ────────────────────────────────────────────
    Route::middleware('company')->group(function () {

        Route::post('/logout', [CompanyAuthController::class, 'logout'])->name('logout');

        // Dashboard — toujours accessible (aucune permission spécifique requise)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Chauffeurs — CRUD complet
        Route::middleware('company.permission:drivers')->group(function () {
            Route::get('/drivers',                [DriverController::class, 'index'])->name('drivers.index');
            Route::get('/drivers/create',         [DriverController::class, 'create'])->name('drivers.create');
            Route::post('/drivers',               [DriverController::class, 'store'])->name('drivers.store');
            Route::get('/drivers/{id}',           [DriverController::class, 'show'])->name('drivers.show');
            Route::get('/drivers/{id}/edit',      [DriverController::class, 'edit'])->name('drivers.edit');
            Route::put('/drivers/{id}',           [DriverController::class, 'update'])->name('drivers.update');
            Route::post('/drivers/{id}/activate', [DriverController::class, 'activate'])->name('drivers.activate');
            Route::post('/drivers/{id}/suspend',  [DriverController::class, 'suspend'])->name('drivers.suspend');
            Route::post('/drivers/{id}/assign',   [DriverController::class, 'assign'])->name('drivers.assign');
            Route::post('/drivers/{id}/remove',   [DriverController::class, 'remove'])->name('drivers.remove');
        });

        // Véhicules
        Route::middleware('company.permission:vehicles')->group(function () {
            Route::get('/vehicles',           [VehicleController::class, 'index'])->name('vehicles.index');
            Route::get('/vehicles/create',    [VehicleController::class, 'create'])->name('vehicles.create');
            Route::post('/vehicles',          [VehicleController::class, 'store'])->name('vehicles.store');
            Route::get('/vehicles/{id}',      [VehicleController::class, 'show'])->name('vehicles.show');
            Route::get('/vehicles/{id}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
            Route::put('/vehicles/{id}',      [VehicleController::class, 'update'])->name('vehicles.update');
            Route::delete('/vehicles/{id}',   [VehicleController::class, 'destroy'])->name('vehicles.destroy');

            // Véhicules — association chauffeurs / créneaux
            Route::post('/vehicles/{id}/shifts',                 [VehicleController::class, 'storeShift'])->name('vehicles.shifts.store');
            Route::delete('/vehicles/{id}/shifts/{shiftId}',     [VehicleController::class, 'destroyShift'])->name('vehicles.shifts.destroy');
        });

        // Planning chauffeurs (calendrier)
        Route::middleware('company.permission:schedule')->group(function () {
            Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
        });

        // Réservations / courses
        Route::middleware('company.permission:reservations')->group(function () {
            Route::get('/reservations',       [ReservationController::class, 'index'])->name('reservations.index');
            Route::get('/reservations/{id}',  [ReservationController::class, 'show'])->name('reservations.show');
        });

        // Grilles tarifaires
        Route::middleware('company.permission:pricing_grids')->group(function () {
            Route::get('/pricing-grids',            [PricingGridController::class, 'index'])->name('pricing-grids.index');
            Route::get('/pricing-grids/create',     [PricingGridController::class, 'create'])->name('pricing-grids.create');
            Route::post('/pricing-grids',           [PricingGridController::class, 'store'])->name('pricing-grids.store');
            Route::get('/pricing-grids/{id}',       [PricingGridController::class, 'show'])->name('pricing-grids.show');
            Route::get('/pricing-grids/{id}/edit',  [PricingGridController::class, 'edit'])->name('pricing-grids.edit');
            Route::put('/pricing-grids/{id}',       [PricingGridController::class, 'update'])->name('pricing-grids.update');
            Route::delete('/pricing-grids/{id}',    [PricingGridController::class, 'destroy'])->name('pricing-grids.destroy');
            Route::post('/pricing-grids/{id}/rates',              [PricingGridController::class, 'storeRate'])->name('pricing-grids.rates.store');
            Route::delete('/pricing-grids/{id}/rates/{rateId}',   [PricingGridController::class, 'destroyRate'])->name('pricing-grids.rates.destroy');
        });

        // Itinéraires
        Route::middleware('company.permission:itineraries')->group(function () {
            Route::get('/itineraries',            [ItineraryController::class, 'index'])->name('itineraries.index');
            Route::get('/itineraries/create',     [ItineraryController::class, 'create'])->name('itineraries.create');
            Route::post('/itineraries',           [ItineraryController::class, 'store'])->name('itineraries.store');
            Route::get('/itineraries/{id}/edit',  [ItineraryController::class, 'edit'])->name('itineraries.edit');
            Route::put('/itineraries/{id}',       [ItineraryController::class, 'update'])->name('itineraries.update');
            Route::delete('/itineraries/{id}',    [ItineraryController::class, 'destroy'])->name('itineraries.destroy');
            Route::post('/itineraries/{id}/toggle',[ItineraryController::class, 'toggle'])->name('itineraries.toggle');
        });

        // Revenus
        Route::middleware('company.permission:revenus')->group(function () {
            Route::get('/revenus', [RevenueController::class, 'index'])->name('revenus.index');
        });

        // Retraits (mensuels)
        Route::middleware('company.permission:withdrawals')->group(function () {
            Route::get('/withdrawals',            [WithdrawalController::class, 'index'])->name('withdrawals.index');
            Route::post('/withdrawals',            [WithdrawalController::class, 'store'])->name('withdrawals.store');
            Route::post('/withdrawals/bank-info',  [WithdrawalController::class, 'updateBankInfo'])->name('withdrawals.bank-info');
        });

        // Appels voix in-app (support + clients)
        Route::middleware('company.permission:messages')->group(function () {
            Route::post('/calls/initiate',       [CompanyCallController::class, 'initiate'])->name('calls.initiate');
            Route::post('/calls/{callId}/answer', [CompanyCallController::class, 'answer'])->name('calls.answer');
            Route::post('/calls/{callId}/end',    [CompanyCallController::class, 'end'])->name('calls.end');
            Route::post('/calls/{callId}/missed', [CompanyCallController::class, 'missed'])->name('calls.missed');
            Route::get('/calls/{callId}/token',   [CompanyCallController::class, 'token'])->name('calls.token');
        });

        // Messages
        Route::middleware('company.permission:messages')->group(function () {
            Route::get('/messages',         [CompanyMessageController::class, 'index'])->name('messages.index');
            Route::get('/messages/support', [CompanyMessageController::class, 'support'])->name('messages.support');
            Route::get('/messages/{trip}',  [CompanyMessageController::class, 'show'])->name('messages.show');
        });

        // Agents de la société (comptable, RH, DG, flotte, marketing, commercial)
        Route::middleware('company.permission:agents')->group(function () {
            Route::get('/agents',              [AgentController::class, 'index'])->name('agents.index');
            Route::get('/agents/create',       [AgentController::class, 'create'])->name('agents.create');
            Route::post('/agents',             [AgentController::class, 'store'])->name('agents.store');
            Route::get('/agents/{id}/edit',    [AgentController::class, 'edit'])->name('agents.edit');
            Route::put('/agents/{id}',         [AgentController::class, 'update'])->name('agents.update');
            Route::post('/agents/{id}/suspend',[AgentController::class, 'suspend'])->name('agents.suspend');
            Route::post('/agents/{id}/activate',[AgentController::class, 'activate'])->name('agents.activate');
            Route::delete('/agents/{id}',      [AgentController::class, 'destroy'])->name('agents.destroy');
        });

    });
});
