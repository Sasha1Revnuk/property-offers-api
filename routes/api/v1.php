<?php

use App\Http\Controllers\Api\V1\Import\ImportController;
use App\Http\Controllers\Api\V1\Property\PropertyController;
use App\Http\Controllers\Api\V1\Reservation\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Versioned product routes under /api/v1.
|
*/

Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
Route::get('imports/{import}', [ImportController::class, 'show'])->name('imports.show');
Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
Route::post('offers/{offer}/reservations', [ReservationController::class, 'store'])
    ->name('offers.reservations.store');
