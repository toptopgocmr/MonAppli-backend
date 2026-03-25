<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app'     => 'TopTopGo API',
        'version' => '1.0.0',
        'status'  => 'running',
    ]);
});
