<?php

use App\Http\Controllers\Accounting\CustomerController;
use App\Http\Controllers\Accounting\DailyTxController;
use App\Http\Controllers\Accounting\DeliveryController;
use App\Http\Controllers\Accounting\GiroController;
use App\Http\Controllers\Accounting\SapSyncController;
use App\Http\Controllers\Accounting\VatController;
use App\Http\Controllers\Accounting\Wtax23Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingPayreqController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ExchangeRateController;

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
    Route::prefix('daily-tx')->name('daily-tx.')->group(function () {
        Route::get('/data', [DailyTxController::class, 'data'])->name('data');
        Route::get('/wtax23-data', [DailyTxController::class, 'wtax23data'])->name('wtax23data');
        Route::get('/', [DailyTxController::class, 'index'])->name('index');
        Route::get('/detail', [DailyTxController::class, 'detail'])->name('detail');
        Route::get('/by-user', [DailyTxController::class, 'by_user'])->name('by_user');
        Route::post('/upload', [DailyTxController::class, 'upload'])->name('upload');
        Route::get('/truncate', [DailyTxController::class, 'truncate'])->name('truncate');
        Route::post('/upload-wtax23', [DailyTxController::class, 'uploadWtax23'])->name('uploadWtax23');
        Route::get('/copy-to-inv-creation', [DailyTxController::class, 'copyToInvoiceCreation'])->name('copyToInvoiceCreation');
        Route::get('/copy-to-fakturs', [DailyTxController::class, 'copyToFakturs'])->name('copyToFakturs');
    });

    // Wtax23
    Route::prefix('wtax23')->name('wtax23.')->group(function () {
        Route::get('data', [Wtax23Controller::class, 'data'])->name('data');
        Route::get('/', [Wtax23Controller::class, 'index'])->name('index');
        Route::put('/{id}/update', [Wtax23Controller::class, 'update'])->name('update');
    });

    Route::prefix('vat')->name('vat.')->group(function () {
        Route::get('data', [VatController::class, 'data'])->name('data');
        Route::get('/', [VatController::class, 'index'])->name('index');
        Route::put('/{id}/purchase-update', [VatController::class, 'purchase_update'])->name('purchase_update');
        Route::put('/{id}/sales-update', [VatController::class, 'sales_update'])->name('sales_update');
        Route::get('/search/data', [VatController::class, 'search_data'])->name('search.data');
        Route::get('show/{faktur}', [VatController::class, 'show'])->name('show');
        Route::put('update/{faktur}', [VatController::class, 'update'])->name('update');
    });

    // DELIVERY
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/data', [DeliveryController::class, 'data'])->name('data');
        Route::get('/receive-data', [DeliveryController::class, 'receive_data'])->name('receive_data');
        Route::resource('/', DeliveryController::class)->parameters(['' => 'delivery']);
        Route::get('/{id}/print', [DeliveryController::class, 'print'])->name('print');
        Route::post('/{id}/send', [DeliveryController::class, 'send'])->name('send');
        Route::get('/{id}/receive', [DeliveryController::class, 'receive_show'])->name('receive_show');
        Route::put('/{id}/receive-update', [DeliveryController::class, 'updateReceiveInfo'])->name('receive_update');
        Route::get('/dashboard-data', [DeliveryController::class, 'dashboard_data'])->name('dashboard-data');
    });

    // EXCHANGE RATES
    Route::prefix('exchange-rates')->name('exchange-rates.')->group(function () {
        Route::get('/data', [ExchangeRateController::class, 'data'])->name('data');
        Route::put('/bulk-update', [ExchangeRateController::class, 'bulkUpdate'])->name('bulk-update');
        Route::delete('/bulk-delete', [ExchangeRateController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/import', [ExchangeRateController::class, 'import'])->name('import');
        Route::get('/export', [ExchangeRateController::class, 'export'])->name('export');
        Route::get('/template', [ExchangeRateController::class, 'downloadTemplate'])->name('template');
        Route::resource('/', ExchangeRateController::class)->parameters(['' => 'exchange_rate']);
    });
});
