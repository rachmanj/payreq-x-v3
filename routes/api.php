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

// Exchange Rates API for Dashboard (no auth required)
Route::get('/dashboard/exchange-rate-usd', function () {
    try {
        $todayRate = \App\Models\ExchangeRate::where('currency_from', 'USD')
            ->where('currency_to', 'IDR')
            ->where('source', 'automated')
            ->whereDate('effective_date', today())
            ->orderByDesc('scraped_at')
            ->first();

        if ($todayRate) {
            return response()->json([
                'success' => true,
                'rate' => $todayRate->exchange_rate,
                'scraped_at' => $todayRate->scraped_at,
                'kmk_number' => $todayRate->kmk_number,
                'effective_date' => $todayRate->effective_date,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No automated USD rate found for today',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching USD rate: ' . $e->getMessage(),
        ], 500);
    }
})->name('api.dashboard.exchange-rate-usd');

// Payment Request API Routes
Route::prefix('payreqs')->middleware('auth.apikey')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\PayreqApiController::class, 'index']);
    Route::post('/advance', [App\Http\Controllers\Api\PayreqApiController::class, 'storeAdvance']);
    Route::post('/reimburse', [App\Http\Controllers\Api\PayreqApiController::class, 'storeReimburse']);
    Route::get('/rabs', [App\Http\Controllers\Api\PayreqApiController::class, 'getRabs']);
    Route::get('/{id}', [App\Http\Controllers\Api\PayreqApiController::class, 'show']);
    Route::post('/{id}/cancel', [App\Http\Controllers\Api\PayreqApiController::class, 'cancel']);
});
