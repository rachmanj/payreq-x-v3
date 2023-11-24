<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPayreqController;
use App\Http\Controllers\UserOngoingController;
use App\Http\Controllers\PayreqAdvanceController;
use App\Http\Controllers\PayreqReimburseController;
use App\Http\Controllers\UserRealizationController;
use App\Http\Controllers\UserPayreqHistoriesController;

Route::prefix('user-payreqs')->name('user-payreqs.')->group(function () {
    // ONGOINGS
    Route::prefix('ongoings')->name('ongoings.')->group(function () {
        Route::get('/', [UserOngoingController::class, 'index'])->name('index');
        Route::get('/data', [UserOngoingController::class, 'data'])->name('data');
    });

    // HISTORIES
    Route::prefix('histories')->name('histories.')->group(function () {
        Route::get('/data', [UserPayreqHistoriesController::class, 'data'])->name('data');
        Route::get('/', [UserPayreqHistoriesController::class, 'index'])->name('index');
        Route::get('/{payreq_id}/show', [UserPayreqHistoriesController::class, 'show'])->name('show');
    });

    // REALIZATION
    Route::prefix('realizations')->name('realizations.')->group(function () {
        Route::get('/data', [UserRealizationController::class, 'data'])->name('data');
        Route::get('/', [UserRealizationController::class, 'index'])->name('index');
        // add realization detials
        Route::get('/{realization_id}/add_details', [UserRealizationController::class, 'add_details'])->name('add_details');
        Route::post('/store_detail', [UserRealizationController::class, 'store_detail'])->name('store_detail');
        Route::post('/submit', [UserRealizationController::class, 'submit_realization'])->name('submit_realization');
        Route::delete('/{realization_detail_id}/delete_detail', [UserRealizationController::class, 'delete_detail'])->name('delete_detail');
        Route::get('/{realization_id}/print', [UserRealizationController::class, 'print'])->name('print');
        Route::put('/{realization_id}/void', [UserRealizationController::class, 'void'])->name('void');
        Route::delete('/{realization_id}/cancel', [UserRealizationController::class, 'cancel'])->name('cancel');
    });
    Route::resource('realizations', UserRealizationController::class);

    // PAYREQS
    Route::get('/data', [UserPayreqController::class, 'data'])->name('data');
    Route::get('/', [UserPayreqController::class, 'index'])->name('index');
    Route::get('/{id}', [UserPayreqController::class, 'show'])->name('show');
    Route::post('/cancel', [UserPayreqController::class, 'cancel'])->name('cancel');
    Route::put('/{id}', [UserPayreqController::class, 'destroy'])->name('destroy');
    // print pdf
    Route::get('/{id}/print', [UserPayreqController::class, 'print'])->name('print');



    //REIMBURSE TYPE
    Route::prefix('reimburse')->name('reimburse.')->group(function () {
        Route::get('/create', [PayreqReimburseController::class, 'create'])->name('create');
        Route::post('/submit', [PayreqReimburseController::class, 'submit_payreq'])->name('submit_payreq');
        Route::post('/store-detail', [PayreqReimburseController::class, 'store_detail'])->name('store_detail');
        Route::post('/delete-detail', [PayreqReimburseController::class, 'delete_detail'])->name('delete_detail');
        // Route::delete('/{realization_detail_id}/delete_detail', [PayreqReimburseController::class, 'delete_detail'])->name('delete_detail');
    });
});

// PAYREQ ADVANCE
Route::resource('payreq-advance', PayreqAdvanceController::class);

// PAYREQ OTHER
Route::resource('payreq-reimburse', PayreqReimburseController::class);
