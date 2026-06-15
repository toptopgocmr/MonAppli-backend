<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app'     => 'TopTopGo API',
        'version' => '1.0.0',
        'status'  => 'running',
    ]);
});

// ── Pages légales ─────────────────────────────────────────────────────────
Route::get('/privacy-policy/driver', function () {
    return view('privacy-driver');
})->name('privacy.driver');

Route::get('/privacy-policy/client', function () {
    return view('privacy-driver'); // même page — adapter si nécessaire
})->name('privacy.client');
