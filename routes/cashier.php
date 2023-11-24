<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashierApprovedController;
use App\Http\Controllers\CashierOutgoingController;
use App\Http\Controllers\CashierIncomingController;

Route::prefix('cashier')->name('cashier.')->group(function () {
    // APPROVEDS PAYREQS -> ready to pay
    Route::prefix('approveds')->name('approveds.')->group(function () {
        Route::get('/data', [CashierApprovedController::class, 'data'])->name('data');
        Route::get('/', [CashierApprovedController::class, 'index'])->name('index');
        Route::put('/{id}/auto', [CashierApprovedController::class, 'auto_outgoing'])->name('auto_outgoing');
        Route::get('/{id}/pay', [CashierApprovedController::class, 'pay'])->name('pay');
        Route::put('/{id}/pay', [CashierApprovedController::class, 'store_pay'])->name('store_pay');
    });

    Route::prefix('outgoings')->name('outgoings.')->group(function () {
        Route::get('/data', [CashierOutgoingController::class, 'data'])->name('data');
        Route::get('/', [CashierOutgoingController::class, 'index'])->name('index');
    });

    Route::prefix('incomings')->name('incomings.')->group(function () {
        Route::get('/data', [CashierIncomingController::class, 'data'])->name('data');
        Route::get('/', [CashierIncomingController::class, 'index'])->name('index');
        Route::post('/receive', [CashierIncomingController::class, 'receive'])->name('receive');
    });
});
