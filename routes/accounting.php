<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingPayreqController;

Route::prefix('accounting')->name('accounting.')->group(function () {
    // PAYREQS LIST
    Route::prefix('payreqs')->name('payreqs.')->group(function () {
        Route::get('/data', [AccountingPayreqController::class, 'data'])->name('data');
        Route::get('/', [AccountingPayreqController::class, 'index'])->name('index');
        Route::get('/create', [AccountingPayreqController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [AccountingPayreqController::class, 'edit'])->name('edit');
        Route::delete('/{id}/destroy', [AccountingPayreqController::class, 'destroy'])->name('destroy');
        Route::post('/store', [AccountingPayreqController::class, 'store'])->name('store');
    });
});
