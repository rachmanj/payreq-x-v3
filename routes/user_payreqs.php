<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPayreqController;
use App\Http\Controllers\UserOngoingController;
use App\Http\Controllers\UserPayreq\FakturController;
use App\Http\Controllers\UserPayreq\PayreqAdvanceController;
use App\Http\Controllers\UserPayreq\PayreqReimburseController;
use App\Http\Controllers\UserPayreq\UserAnggaranController;
use App\Http\Controllers\UserRealizationController;
use App\Http\Controllers\UserPayreqHistoriesController;

Route::prefix('user-payreqs')->name('user-payreqs.')->group(function () {

    // ANGGARAN
    Route::prefix('anggarans')->name('anggarans.')->group(function () {
        Route::get('/data', [UserAnggaranController::class, 'data'])->name('data');
        Route::get('/', [UserAnggaranController::class, 'index'])->name('index');
        Route::get('/create', [UserAnggaranController::class, 'create'])->name('create');
        Route::post('/proses', [UserAnggaranController::class, 'proses'])->name('proses');
        Route::get('/{id}/show', [UserAnggaranController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UserAnggaranController::class, 'edit'])->name('edit');
        Route::get('/{id}/data', [UserAnggaranController::class, 'payreqs_data'])->name('payreqs_data');
    });

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
        Route::delete('/{payreq_id}', [UserPayreqHistoriesController::class, 'destroy'])->name('destroy');
    });

    // REALIZATION
    Route::prefix('realizations')->name('realizations.')->group(function () {
        Route::get('/data', [UserRealizationController::class, 'data'])->name('data');
        Route::get('/', [UserRealizationController::class, 'index'])->name('index');
        // add realization details
        Route::get('/{realization_id}/add_details', [UserRealizationController::class, 'add_details'])->name('add_details');
        Route::post('/store_detail', [UserRealizationController::class, 'store_detail'])->name('store_detail');
        Route::post('/submit', [UserRealizationController::class, 'submit_realization'])->name('submit_realization');
        Route::delete('/{realization_detail_id}/delete_detail', [UserRealizationController::class, 'delete_detail'])->name('delete_detail');
        Route::get('/{realization_id}/print', [UserRealizationController::class, 'print'])->name('print');
        Route::put('/{realization_id}/void', [UserRealizationController::class, 'void'])->name('void');
        Route::delete('/{realization_id}/cancel', [UserRealizationController::class, 'cancel'])->name('cancel');

        // New AJAX routes for realization details
        Route::get('/get_details/{realization}', [UserRealizationController::class, 'getDetails'])->name('get_details');
        Route::get('/get_detail/{detail}', [UserRealizationController::class, 'getDetail'])->name('get_detail');
        Route::post('/update_detail/{detail}', [UserRealizationController::class, 'updateDetail'])->name('update_detail');
    });
    Route::resource('realizations', UserRealizationController::class);

    // PAYREQS INDEX
    Route::get('/data', [UserPayreqController::class, 'data'])->name('data');
    Route::get('/', [UserPayreqController::class, 'index'])->name('index');
    Route::get('/{id}', [UserPayreqController::class, 'show'])->name('show');
    Route::post('/cancel', [UserPayreqController::class, 'cancel'])->name('cancel');
    Route::put('/{id}', [UserPayreqController::class, 'destroy'])->name('destroy');
    // print pdf
    Route::get('/{id}/print', [UserPayreqController::class, 'print'])->name('print');

    // PAYREQ ADVANCE
    Route::prefix('advance')->name('advance.')->group(function () {
        Route::get('/', [PayreqAdvanceController::class, 'index'])->name('index');
        Route::get('/create', [PayreqAdvanceController::class, 'create'])->name('create');
        Route::post('/proses', [PayreqAdvanceController::class, 'proses'])->name('proses');
        Route::get('/{id}/edit', [PayreqAdvanceController::class, 'edit'])->name('edit');
    });

    //REIMBURSE TYPE
    Route::prefix('reimburse')->name('reimburse.')->group(function () {
        Route::get('/create', [PayreqReimburseController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [PayreqReimburseController::class, 'edit'])->name('edit');
        Route::post('/submit', [PayreqReimburseController::class, 'submit_payreq'])->name('submit_payreq');
        Route::post('/store', [PayreqReimburseController::class, 'store'])->name('store');
        Route::post('/store-detail', [PayreqReimburseController::class, 'store_detail'])->name('store_detail');
        Route::post('/delete-detail', [PayreqReimburseController::class, 'delete_detail'])->name('delete_detail');
        Route::post('/update-rab', [PayreqReimburseController::class, 'update_rab'])->name('update_rab');
    });

    //FAKTURS
    Route::prefix('fakturs')->name('fakturs.')->group(function () {
        Route::get('/index', [FakturController::class, 'index'])->name('index');
        Route::get('/data', [FakturController::class, 'data'])->name('data');
        Route::post('/store', [FakturController::class, 'store'])->name('store');
        Route::delete('/{id}', [FakturController::class, 'destroy'])->name('destroy');
        Route::put('/{id}', [FakturController::class, 'update_arinvoice'])->name('update_arinvoice');
        Route::post('/update-faktur', [FakturController::class, 'update_faktur'])->name('update_faktur');
    });
});

// PAYREQ OTHER
// Route::resource('payreq-reimburse', PayreqReimburseController::class);
