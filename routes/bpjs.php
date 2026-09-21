<?php

use App\Http\Controllers\Accounting\BpjsApInvoiceController;
use App\Http\Controllers\OpVoucherPrintController;
use Illuminate\Support\Facades\Route;

Route::prefix('bpjs-ap-invoices')
    ->name('bpjs-ap-invoices.')
    ->middleware('permission:akses_ap_invoice_bpjs')
    ->group(function () {
        Route::get('/', [BpjsApInvoiceController::class, 'index'])->name('index');
        Route::get('/data', [BpjsApInvoiceController::class, 'data'])->name('data');
        Route::get('/{bpjsApInvoice}/print-op', [OpVoucherPrintController::class, 'printBpjs'])->name('print-op');

        Route::middleware('permission:submit_sap_ap_invoice_bpjs')->group(function () {
            Route::get('/last-amount', [BpjsApInvoiceController::class, 'lastAmount'])->name('last-amount');
            Route::post('/', [BpjsApInvoiceController::class, 'store'])->name('store');
            Route::get('/{bpjsApInvoice}/preview', [BpjsApInvoiceController::class, 'preview'])->name('preview');
            Route::post('/{bpjsApInvoice}/submit', [BpjsApInvoiceController::class, 'submit'])->name('submit');
            Route::post('/{bpjsApInvoice}/retry', [BpjsApInvoiceController::class, 'retry'])->name('retry');
            Route::post('/{bpjsApInvoice}/retry-je', [BpjsApInvoiceController::class, 'retryJe'])->name('retry-je');
            Route::post('/sync-sap-status', [BpjsApInvoiceController::class, 'syncSapStatusAll'])->name('sync-sap-status-all');
            Route::post('/{bpjsApInvoice}/sync-sap-status', [BpjsApInvoiceController::class, 'syncSapStatus'])->name('sync-sap-status');
        });

        Route::middleware('permission:cancel_sap_ap_invoice_bpjs')->group(function () {
            Route::post('/{bpjsApInvoice}/cancel', [BpjsApInvoiceController::class, 'cancel'])->name('cancel');
            Route::post('/{bpjsApInvoice}/cancel-je', [BpjsApInvoiceController::class, 'cancelJe'])->name('cancel-je');
        });
    });
