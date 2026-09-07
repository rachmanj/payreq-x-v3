<?php

use App\Http\Controllers\Accounting\BpjsApInvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('bpjs-ap-invoices')
    ->name('bpjs-ap-invoices.')
    ->middleware('permission:akses_ap_invoice_bpjs')
    ->group(function () {
        Route::get('/', [BpjsApInvoiceController::class, 'index'])->name('index');
        Route::get('/data', [BpjsApInvoiceController::class, 'data'])->name('data');

        Route::middleware('permission:submit_sap_ap_invoice_bpjs')->group(function () {
            Route::get('/last-amount', [BpjsApInvoiceController::class, 'lastAmount'])->name('last-amount');
            Route::post('/', [BpjsApInvoiceController::class, 'store'])->name('store');
            Route::get('/{bpjsApInvoice}/preview', [BpjsApInvoiceController::class, 'preview'])->name('preview');
            Route::post('/{bpjsApInvoice}/submit', [BpjsApInvoiceController::class, 'submit'])->name('submit');
            Route::post('/{bpjsApInvoice}/retry', [BpjsApInvoiceController::class, 'retry'])->name('retry');
        });
    });
