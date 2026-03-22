<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PortalFeedController;
use App\Http\Controllers\PortalStatsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/portal', [PortalFeedController::class, 'index']);
Route::get('/portal-stats', [PortalStatsController::class, 'index']);