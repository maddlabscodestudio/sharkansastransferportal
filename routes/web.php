<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PortalFeedController;
use App\Http\Controllers\PortalStatsController;
use App\Http\Controllers\PortalStatsManageController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/portal', [PortalFeedController::class, 'index']);
Route::get('/portal-stats', [PortalStatsController::class, 'index']);


Route::get('/portal-stats-manage', [PortalStatsManageController::class, 'index']);
Route::delete('/portal-stats-manage/event/{id}', [PortalStatsManageController::class, 'destroyEvent'])
    ->name('portal-stats-manage.destroy-event');