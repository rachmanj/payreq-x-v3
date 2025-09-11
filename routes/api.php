<?php

use App\Http\Controllers\Api\BilyetApiController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\BucSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('rabs/get-payreqs', [BucSyncController::class, 'get_buc_payreqs'])->name('get_buc_payreqs');
Route::get('/projects', [ProjectController::class, 'get_projects']);
Route::get('/customers', [CustomerController::class, 'get_customers']);

// Bilyet API Routes
Route::prefix('bilyets')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [BilyetApiController::class, 'index']);
    Route::post('/', [BilyetApiController::class, 'store']);
    Route::get('/statistics', [BilyetApiController::class, 'statistics']);
    Route::get('/audit-trail', [BilyetApiController::class, 'auditTrail']);
    Route::post('/bulk-update', [BilyetApiController::class, 'bulkUpdate']);
    Route::get('/{id}', [BilyetApiController::class, 'show']);
    Route::put('/{id}', [BilyetApiController::class, 'update']);
    Route::delete('/{id}', [BilyetApiController::class, 'destroy']);
});

/*
 * Route for getting sum of amounts by type for a specific unit_no
 * No authentication required
 */
Route::get('/realization-details/sum-by-unit', [App\Http\Controllers\Api\RealizationDetailController::class, 'sumByUnitNo']);
