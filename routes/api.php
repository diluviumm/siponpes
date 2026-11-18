<?php

use App\Http\Controllers\CanteenApiController;
use Illuminate\Support\Facades\Route;

Route::post('canteen/transaction', [CanteenApiController::class, 'transaction'])
    ->middleware('throttle:60,1');
