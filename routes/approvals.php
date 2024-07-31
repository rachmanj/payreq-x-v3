<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\ApprovalRequestAnggaranController;
use App\Http\Controllers\ApprovalRequestPayreqController;
use App\Http\Controllers\ApprovalRequestRealizationController;


Route::prefix('approvals')->name('approvals.')->group(function () {
    Route::prefix('request')->name('request.')->group(function () {
        Route::prefix('payreqs')->name('payreqs.')->group(function () {
            Route::get('/data', [ApprovalRequestPayreqController::class, 'data'])->name('data'); // 'approvals.request.payreqs.data'
            Route::get('/', [ApprovalRequestPayreqController::class, 'index'])->name('index'); // 'approvals.request.payreqs.index'
            Route::get('/{id}', [ApprovalRequestPayreqController::class, 'show'])->name('show'); // 'approvals.request.payreqs.show'
        });
        Route::prefix('realizations')->name('realizations.')->group(function () {
            Route::get('/data', [ApprovalRequestRealizationController::class, 'data'])->name('data');
            Route::get('/', [ApprovalRequestRealizationController::class, 'index'])->name('index');
            Route::get('/{id}', [ApprovalRequestRealizationController::class, 'show'])->name('show');
        });
        Route::prefix('anggarans')->name('anggarans.')->group(function () {
            Route::get('/data', [ApprovalRequestAnggaranController::class, 'data'])->name('data');
            Route::get('/', [ApprovalRequestAnggaranController::class, 'index'])->name('index');
        });
    });
    Route::prefix('plan')->name('plan.')->group(function () {
        Route::put('/{id}/update', [ApprovalPlanController::class, 'update'])->name('update');
    });
});

// APPROVAL STAGES
Route::prefix('approval-stages')->name('approval-stages.')->group(function () {
    Route::get('/data', [ApprovalStageController::class, 'data'])->name('data');
    Route::post('/auto-generate', [ApprovalStageController::class, 'auto_generate'])->name('auto_generate');
});
Route::resource('approval-stages', ApprovalStageController::class);
