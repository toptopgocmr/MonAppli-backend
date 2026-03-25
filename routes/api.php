<?php

use Illuminate\Support\Facades\Route;

// ── Auth Controllers
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\DriverAuthController;
use App\Http\Controllers\Auth\UserAuthController;

// ── Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\SosAlertController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\AdminDriverSupportController;
use App\Http\Controllers\Admin\AdminUserSupportController;

// ── Driver Controllers
use App\Http\Controllers\Driver\DriverTripController;
use App\Http\Controllers\Driver\DriverStatusController;
use App\Http\Controllers\Driver\DriverWalletController;
use App\Http\Controllers\Driver\DriverWithdrawalController;
use App\Http\Controllers\Driver\DriverSosController;
use App\Http\Controllers\Driver\DriverMessageController;   // 🔄 MODIFIÉ (modération)
use App\Http\Controllers\Driver\DriverCallController;      // ✅ NOUVEAU
use App\Http\Controllers\Driver\DriverSupportController;
use App\Http\Controllers\Driver\DriverDocumentController;
use App\Http\Controllers\Driver\DriverPasswordController;
use App\Http\Controllers\Driver\DriverProfileController;

// ── User (Client) Controllers
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\User\UserTripController;
use App\Http\Controllers\User\UserBookingController;
use App\Http\Controllers\User\UserPaymentController;
use App\Http\Controllers\User\UserSupportController;
use App\Http\Controllers\User\UserMessageController;       // 🔄 MODIFIÉ (modération)
use App\Http\Controllers\User\UserCallController;          // ✅ NOUVEAU
use App\Http\Controllers\User\UserPasswordController;

/*
|--------------------------------------------------------------------------
| TEST / STATUS
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return response()->json(['status' => 'success', 'message' => 'API is running']);
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me',     [AdminAuthController::class, 'me']);
    });
});

Route::prefix('driver/auth')->group(function () {
    Route::post('register',        [DriverAuthController::class, 'register']);
    Route::post('login',           [DriverAuthController::class, 'login']);
    Route::post('forgot-password', [DriverAuthController::class, 'forgotPassword']);
    Route::post('reset-password',  [DriverAuthController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [DriverAuthController::class, 'logout']);
        Route::get('me',      [DriverAuthController::class, 'me']);
    });
});

Route::prefix('user/auth')->group(function () {
    Route::post('register',        [UserAuthController::class, 'register']);
    Route::post('login',           [UserAuthController::class, 'login']);
    Route::post('forgot-password', [UserAuthController::class, 'forgotPassword']);
    Route::post('reset-password',  [UserAuthController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [UserAuthController::class, 'logout']);
        Route::get('me',      [UserAuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN API ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('api.admin.')->middleware(['auth:sanctum'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role.permission:Super Admin')->group(function () {
        Route::apiResource('admins', AdminUserController::class)->names('admins');
    });

    Route::middleware('role.permission:Admin')->group(function () {
        Route::apiResource('drivers', DriverController::class)->only(['index','store','show'])->names('drivers');
        Route::apiResource('users',   UserController::class)->only(['index','show'])->names('api.users');
        Route::get('trips', [TripController::class, 'index'])->name('trips');

        Route::get('support/drivers',                [AdminDriverSupportController::class, 'index'])->name('support.drivers.index');
        Route::get('support/drivers/{driver}',       [AdminDriverSupportController::class, 'show'])->name('support.drivers.show');
        Route::post('support/drivers/{driver}/send', [AdminDriverSupportController::class, 'send'])->name('support.drivers.send');

        Route::get('support/users',              [AdminUserSupportController::class, 'index'])->name('support.users.index');
        Route::get('support/users/{user}',       [AdminUserSupportController::class, 'show'])->name('support.users.show');
        Route::post('support/users/{user}/send', [AdminUserSupportController::class, 'send'])->name('support.users.send');
    });

    Route::middleware('role.permission:Finance Manager')->group(function () {
        Route::apiResource('payments',   PaymentController::class)->only(['index','show'])->names('payments');
        Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals');
    });

    Route::middleware('role.permission:Compliance Manager')->group(function () {
        Route::get('documents/pending',  [DocumentController::class, 'pending'])->name('documents.pending');
        Route::get('documents/expiring', [DocumentController::class, 'expiring'])->name('documents.expiring');
        Route::get('sos', [SosAlertController::class, 'index'])->name('sos');
    });

    Route::middleware('role.permission:Commercial Manager')->group(function () {
        Route::get('stats/overview',    [StatisticsController::class, 'overview'])->name('stats.overview');
        Route::get('stats/daily',       [StatisticsController::class, 'daily'])->name('stats.daily');
        Route::get('stats/top-drivers', [StatisticsController::class, 'topDrivers'])->name('stats.top-drivers');
    });
});

/*
|--------------------------------------------------------------------------
| DRIVER API ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('driver')->name('api.driver.')->middleware(['auth:sanctum'])->group(function () {

    // ── Profil ────────────────────────────────────────────────────
    Route::get('profile',        [DriverProfileController::class, 'show'])->name('profile.show');
    Route::put('profile',        [DriverProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/photo', [DriverProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::put('password',       [DriverPasswordController::class, 'update'])->name('password.update');
    Route::put('status',         [DriverStatusController::class, 'update'])->name('status.update');

    // ── Trajets ───────────────────────────────────────────────────
    Route::apiResource('trips', DriverTripController::class)->names('trips');
    Route::post('trips/{id}/start', [DriverTripController::class, 'start'])->name('trips.start');
    Route::post('trips/{id}/end',   [DriverTripController::class, 'end'])->name('trips.end');

    // ── Réservations ──────────────────────────────────────────────
    Route::get('bookings',               [DriverTripController::class, 'bookings'])->name('bookings.index');
    Route::post('bookings/{id}/confirm', [DriverTripController::class, 'confirmBooking'])->name('bookings.confirm');
    Route::post('bookings/{id}/reject',  [DriverTripController::class, 'rejectBooking'])->name('bookings.reject');

    // ── Wallet & Retraits ─────────────────────────────────────────
    Route::get('wallet',           [DriverWalletController::class, 'show'])->name('wallet.show');
    Route::get('withdrawals',      [DriverWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('withdrawals',     [DriverWithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('withdrawals/{id}', [DriverWithdrawalController::class, 'show'])->name('withdrawals.show');

    // ── SOS ───────────────────────────────────────────────────────
    Route::get('sos',  [DriverSosController::class, 'index'])->name('sos.index');
    Route::post('sos', [DriverSosController::class, 'store'])->name('sos.store');

    // ── Messages (avec modération côté serveur) ───────────────────
    Route::get('messages',            [DriverMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{trip_id}',  [DriverMessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{trip_id}', [DriverMessageController::class, 'store'])->name('messages.store');

    // ── ✅ NOUVEAU — Appels voix in-app ───────────────────────────
    Route::post('calls/{tripId}/initiate', [DriverCallController::class, 'initiate'])->name('calls.initiate');
    Route::post('calls/{callId}/answer',   [DriverCallController::class, 'answer'])->name('calls.answer');
    Route::post('calls/{callId}/end',      [DriverCallController::class, 'end'])->name('calls.end');
    Route::post('calls/{callId}/missed',   [DriverCallController::class, 'missed'])->name('calls.missed');
    Route::get('calls/{tripId}',           [DriverCallController::class, 'history'])->name('calls.history');

    // ── Support & Documents ───────────────────────────────────────
    Route::get('support',  [DriverSupportController::class, 'index'])->name('support.index');
    Route::post('support', [DriverSupportController::class, 'store'])->name('support.store');

    Route::get('documents',         [DriverDocumentController::class, 'index'])->name('documents.index');
    Route::post('documents',        [DriverDocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{id}',    [DriverDocumentController::class, 'show'])->name('documents.show');
    Route::delete('documents/{id}', [DriverDocumentController::class, 'destroy'])->name('documents.destroy');
});

/*
|--------------------------------------------------------------------------
| USER (CLIENT) API ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('user')->name('api.user.')->middleware(['auth:sanctum'])->group(function () {

    // ── Profil ────────────────────────────────────────────────────
    Route::get('profile',        [UserProfileController::class, 'show'])->name('profile.show');
    Route::put('profile',        [UserProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/photo', [UserProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::put('password',       [UserPasswordController::class, 'update'])->name('password.update');

    // ── Trajets disponibles ───────────────────────────────────────
    Route::get('trips',      [UserTripController::class, 'index'])->name('trips.index');
    Route::get('trips/{id}', [UserTripController::class, 'show'])->name('trips.show');

    // ── Réservations ──────────────────────────────────────────────
    Route::get('bookings',              [UserBookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings',             [UserBookingController::class, 'store'])->name('bookings.store');
    Route::get('bookings/{id}',         [UserBookingController::class, 'show'])->name('bookings.show');
    Route::post('bookings/{id}/accept', [UserBookingController::class, 'accept'])->name('bookings.accept');
    Route::post('bookings/{id}/reject', [UserBookingController::class, 'reject'])->name('bookings.reject');
    Route::post('bookings/{id}/cancel', [UserBookingController::class, 'cancel'])->name('bookings.cancel');

    // ── Paiements ─────────────────────────────────────────────────
    Route::post('payments/mobile-money', [UserPaymentController::class, 'mobileMoney'])->name('payments.mobile-money');
    Route::post('payments/stripe',       [UserPaymentController::class, 'stripe'])->name('payments.stripe');
    Route::get('payments/status',        [UserPaymentController::class, 'status'])->name('payments.status');

    // ── Messages (avec modération côté serveur) ───────────────────
    Route::get('messages',            [UserMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{trip_id}',  [UserMessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{trip_id}', [UserMessageController::class, 'store'])->name('messages.store');

    // ── ✅ NOUVEAU — Appels voix in-app ───────────────────────────
    Route::post('calls/{tripId}/initiate', [UserCallController::class, 'initiate'])->name('calls.initiate');
    Route::post('calls/{callId}/answer',   [UserCallController::class, 'answer'])->name('calls.answer');
    Route::post('calls/{callId}/end',      [UserCallController::class, 'end'])->name('calls.end');
    Route::post('calls/{callId}/missed',   [UserCallController::class, 'missed'])->name('calls.missed');
    Route::get('calls/{tripId}',           [UserCallController::class, 'history'])->name('calls.history');

    // ── Support & SOS ─────────────────────────────────────────────
    Route::get('support',  [UserSupportController::class, 'index'])->name('support.index');
    Route::post('support', [UserSupportController::class, 'store'])->name('support.store');

    Route::post('sos', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::warning('SOS CLIENT', [
            'user_id' => $request->user()?->id,
            'trip_id' => $request->trip_id,
            'lat'     => $request->latitude,
            'lng'     => $request->longitude,
        ]);
        return response()->json(['success' => true, 'message' => 'SOS reçu']);
    })->name('sos');
});