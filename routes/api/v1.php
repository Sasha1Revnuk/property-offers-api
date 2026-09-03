<?php

use App\Http\Controllers\Api\V1\Import\ImportController;
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
