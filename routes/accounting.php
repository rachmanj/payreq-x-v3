<?php

use App\Http\Controllers\Accounting\CustomerController;
use App\Http\Controllers\Accounting\GiroController;
use App\Http\Controllers\Accounting\InvoiceCreationController;
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
        Route::get('/edit-vjdetail', [SapSyncController::class, 'edit_vjdetail_display'])->name('edit_vjdetail_display');
        Route::post('/upload-journal', [SapSyncController::class, 'upload_sap_journal'])->name('upload_sap_journal');
        Route::post('/update-detail', [SapSyncController::class, 'update_detail'])->name('update_detail');
        Route::get('/edit-vjdetail/data', [SapSyncController::class, 'edit_vjdetail_data'])->name('edit_vjdetail_data');
        Route::post('/cancel_sap_info', [SapSyncController::class, 'cancel_sap_info'])->name('cancel_sap_info');
        Route::post('/update_sap_info', [SapSyncController::class, 'update_sap_info'])->name('update_sap_info');
        Route::get('/print_sapj', [SapSyncController::class, 'print_sapj'])->name('print_sapj');
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

    // GIRO
    Route::prefix('giros')->name('giros.')->group(function () {
        Route::get('data', [GiroController::class, 'data'])->name('data');
        Route::get('/', [GiroController::class, 'index'])->name('index');
        Route::post('/', [GiroController::class, 'store'])->name('store');
        Route::put('/{id}', [GiroController::class, 'update'])->name('update');
        Route::delete('/{id}', [GiroController::class, 'destroy'])->name('destroy');
    });

    //CUSTOMERS
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');

    //INVOICE CREATION
    Route::prefix('invoice-creation')->name('invoice-creation.')->group(function () {
        Route::get('/data', [InvoiceCreationController::class, 'data'])->name('data');
        Route::get('/', [InvoiceCreationController::class, 'index'])->name('index');
        Route::get('/detail', [InvoiceCreationController::class, 'detail'])->name('detail');
        Route::get('/by-user', [InvoiceCreationController::class, 'by_user'])->name('by_user');
        Route::post('/upload', [InvoiceCreationController::class, 'upload'])->name('upload');
    });
});
