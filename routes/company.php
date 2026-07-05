<?php

use App\Http\Controllers\Company\CompanyAuthController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DriverController;
use App\Http\Controllers\Company\VehicleController;
use App\Http\Controllers\Company\ReservationController;
use App\Http\Controllers\Company\RevenueController;
use App\Http\Controllers\Company\ItineraryController;
use App\Http\Controllers\Company\CompanyMessageController;
use App\Http\Controllers\Company\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')->name('company.')->group(function () {

    // ── LOGIN ────────────────────────────────────────────────────
    Route::get('/login',  [CompanyAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CompanyAuthController::class, 'login'])->name('login.post');

    // ── PANEL PROTÉGÉ ────────────────────────────────────────────
    Route::middleware('company')->group(function () {

        Route::post('/logout', [CompanyAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Chauffeurs — CRUD complet
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

        // Véhicules
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

        // Planning chauffeurs (calendrier)
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

        // Réservations / courses
        Route::get('/reservations',       [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}',  [ReservationController::class, 'show'])->name('reservations.show');

        // Itinéraires
        Route::get('/itineraries',            [ItineraryController::class, 'index'])->name('itineraries.index');
        Route::get('/itineraries/create',     [ItineraryController::class, 'create'])->name('itineraries.create');
        Route::post('/itineraries',           [ItineraryController::class, 'store'])->name('itineraries.store');
        Route::get('/itineraries/{id}/edit',  [ItineraryController::class, 'edit'])->name('itineraries.edit');
        Route::put('/itineraries/{id}',       [ItineraryController::class, 'update'])->name('itineraries.update');
        Route::delete('/itineraries/{id}',    [ItineraryController::class, 'destroy'])->name('itineraries.destroy');
        Route::post('/itineraries/{id}/toggle',[ItineraryController::class, 'toggle'])->name('itineraries.toggle');

        // Revenus
        Route::get('/revenus', [RevenueController::class, 'index'])->name('revenus.index');

        // Messages
        Route::get('/messages',         [CompanyMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/support', [CompanyMessageController::class, 'support'])->name('messages.support');
        Route::get('/messages/{trip}',  [CompanyMessageController::class, 'show'])->name('messages.show');

    });
});
