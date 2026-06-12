<?php

use App\Http\Controllers\Company\CompanyAuthController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DriverController;
use App\Http\Controllers\Company\VehicleController;
use App\Http\Controllers\Company\ReservationController;
use App\Http\Controllers\Company\RevenueController;
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

        // Chauffeurs
        Route::get('/drivers',            [DriverController::class, 'index'])->name('drivers.index');
        Route::get('/drivers/{id}',       [DriverController::class, 'show'])->name('drivers.show');
        Route::post('/drivers/{id}/assign', [DriverController::class, 'assign'])->name('drivers.assign');
        Route::post('/drivers/{id}/remove', [DriverController::class, 'remove'])->name('drivers.remove');

        // Véhicules
        Route::get('/vehicles',          [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/create',   [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles',         [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{id}/edit',[VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{id}',     [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{id}',  [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        // Réservations / courses
        Route::get('/reservations',      [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}', [ReservationController::class, 'show'])->name('reservations.show');

        // Revenus
        Route::get('/revenus', [RevenueController::class, 'index'])->name('revenus.index');

    });
});
