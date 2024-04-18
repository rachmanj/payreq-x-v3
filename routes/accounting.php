<?php

use App\Http\Controllers\Accounting\SapSyncController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingPayreqController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\LoanController;

Route::prefix('accounting')->name('accounting.')->group(function () {
    // PAYREQS LIST
    Route::prefix('payreqs')->name('payreqs.')->group(function () {
        Route::get('/data', [AccountingPayreqController::class, 'data'])->name('data');
        Route::get('/', [AccountingPayreqController::class, 'index'])->name('index');
        Route::get('/create', [AccountingPayreqController::class, 'create'])->name('create');
        Route::post('/store', [AccountingPayreqController::class, 'store'])->name('store');
        Route::get('/{id}/show', [AccountingPayreqController::class, 'show'])->name('show');
    });

    // SAP Sync
    Route::prefix('sap-sync')->name('sap-sync.')->group(function () {
        Route::get('/', [SapSyncController::class, 'index'])->name('index');
        Route::get('/data', [SapSyncController::class, 'data'])->name('data');
        Route::get('/export', [SapSyncController::class, 'export'])->name('export');
        Route::get('/{id}/show', [SapSyncController::class, 'show'])->name('show');
        Route::post('/cancel_sap_info', [SapSyncController::class, 'cancel_sap_info'])->name('cancel_sap_info');
        Route::post('/update_sap_info', [SapSyncController::class, 'update_sap_info'])->name('update_sap_info');
    });

    // ANGSURAN
    Route::prefix('/loans')->name('loans.')->group(function () {
        Route::get('/data', [LoanController::class, 'data'])->name('data');
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/create', [LoanController::class, 'create'])->name('create');
        Route::get('/{id}/update', [LoanController::class, 'edit'])->name('edit');
        Route::get('/{id}/show', [LoanController::class, 'show'])->name('show');
        Route::put('/{id}', [LoanController::class, 'update'])->name('update');
        Route::post('/', [LoanController::class, 'store'])->name('store');
        Route::delete('/{id}', [LoanController::class, 'destroy'])->name('destroy');

        Route::prefix('/installments')->name('installments.')->group(function () {
            Route::get('/{loan_id}/data', [InstallmentController::class, 'data'])->name('data');
            Route::get('/create', [InstallmentController::class, 'create'])->name('create');
            Route::get('/{loan_id}/generate', [InstallmentController::class, 'generate'])->name('generate');
            Route::post('/store_generate', [InstallmentController::class, 'store_generate'])->name('store_generate');
            Route::get('/{id}/edit', [InstallmentController::class, 'edit'])->name('edit');
            Route::post('/update', [InstallmentController::class, 'update'])->name('update');
            Route::delete('/{id}/destroy', [InstallmentController::class, 'destroy'])->name('destroy');
        });
    });
});
