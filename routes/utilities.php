<?php

use App\Http\Controllers\Utilities\UtilityBillController;
use App\Http\Controllers\Utilities\UtilityCustomerController;
use App\Http\Controllers\Utilities\UtilityDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('utilities')->name('utilities.')->middleware('permission:akses_utilities')->group(function () {
    Route::get('/', [UtilityDashboardController::class, 'index'])->name('dashboard');
    Route::get('/bills', [UtilityBillController::class, 'index'])->name('bills.index');
    Route::get('/bills/data', [UtilityBillController::class, 'data'])->name('bills.data');
    Route::get('/bills/create', [UtilityBillController::class, 'create'])->name('bills.create');
    Route::get('/bills/upload', [UtilityBillController::class, 'upload'])->name('bills.upload');
    Route::post('/bills/parse-upload', [UtilityBillController::class, 'parseUpload'])->name('bills.parse-upload');
    Route::get('/bills/preview', [UtilityBillController::class, 'preview'])->name('bills.preview');
    Route::post('/bills/store-upload', [UtilityBillController::class, 'storeUpload'])->name('bills.store-upload');
    Route::post('/bills', [UtilityBillController::class, 'store'])->name('bills.store');
    Route::post('/bills/copy-last-month', [UtilityBillController::class, 'copyLastMonth'])->name('bills.copy-last-month');
    Route::post('/bills/create-payreq', [UtilityBillController::class, 'createPayreq'])->name('bills.create-payreq');
    Route::post('/bills/{bill}/mark-paid', [UtilityBillController::class, 'markPaid'])->name('bills.mark-paid');
    Route::post('/bills/{bill}/unmark-paid', [UtilityBillController::class, 'unmarkPaid'])->name('bills.unmark-paid');

    Route::get('/customers/data', [UtilityCustomerController::class, 'data'])->name('customers.data');
    Route::resource('/customers', UtilityCustomerController::class);
});
