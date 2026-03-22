<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PortalFeedController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/portal', [PortalFeedController::class, 'index']);
