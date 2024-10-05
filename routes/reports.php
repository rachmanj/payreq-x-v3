<?php

use App\Http\Controllers\Reports\AnggaranController;
use App\Http\Controllers\Reports\BilyetController;
use App\Http\Controllers\Reports\CashierRekapAdvanceController;
use App\Http\Controllers\Reports\EomController;
use App\Http\Controllers\Reports\LoanController;
use App\Http\Controllers\Reports\OngoingDashboardController;
use App\Http\Controllers\Reports\EquipmentController;
use App\Http\Controllers\Reports\OngoingController;
use App\Http\Controllers\Reports\PayreqAgingController;
use App\Http\Controllers\Reports\PeriodeAnggaranController;
use App\Http\Controllers\Reports\ReportCashierController;
use App\Http\Controllers\Reports\ReportIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportIndexController::class, 'index'])->name('index');

    Route::prefix('ongoing')->name('ongoing.')->group(function () {
        Route::get('/', [OngoingController::class, 'index'])->name('index');
        Route::get('/data', [OngoingController::class, 'data'])->name('data');
        Route::get('/{int}/project', [OngoingController::class, 'project_index'])->name('project');

        Route::get('/dashboard', [OngoingDashboardController::class, 'dashboard'])->name('dashboard');

        Route::prefix('payreq-aging')->name('payreq-aging.')->group(function () {
            Route::get('/', [PayreqAgingController::class, 'index'])->name('index');
            Route::get('/data', [PayreqAgingController::class, 'data'])->name('data');
        });
    });

    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::get('/', [EquipmentController::class, 'index'])->name('index');
        Route::get('/data', [EquipmentController::class, 'data'])->name('data');
        // add route with query of unit_no
        Route::get('/unit_no', [EquipmentController::class, 'detail'])->name('detail');
    });

    Route::prefix('loan')->name('loan.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/index-7997', [LoanController::class, 'index_7997'])->name('index_7997');
        Route::get('/index-all', [LoanController::class, 'index_all'])->name('index_all');
        Route::get('/data', [LoanController::class, 'data'])->name('data');
        Route::get('/paid-data', [LoanController::class, 'paid_data'])->name('paid_data');
        Route::post('/update', [LoanController::class, 'update'])->name('update');
        Route::get('/dashboard', [LoanController::class, 'dashboard'])->name('dashboard');
    });

    Route::prefix('eom')->name('eom.')->group(function () {
        Route::get('/data', [EomController::class, 'data'])->name('data');
        Route::get('/', [EomController::class, 'index'])->name('index');
        Route::get('/{id}/show', [EomController::class, 'show'])->name('show');
        Route::get('/export', [EomController::class, 'export'])->name('export');
        Route::post('/store', [EomController::class, 'store'])->name('store');
        Route::post('/cancel_sap_info', [EomController::class, 'cancel_sap_info'])->name('cancel_sap_info');
        Route::post('/update_sap_info', [EomController::class, 'update_sap_info'])->name('update_sap_info');
    });

    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/', [ReportCashierController::class, 'index'])->name('index');

        Route::prefix('rekap-advance')->name('rekap-advance.')->group(function () {
            Route::get('/', [CashierRekapAdvanceController::class, 'index'])->name('index');
        });
    });

    Route::prefix('periode-anggaran')->name('periode-anggaran.')->group(function () {
        Route::get('/', [PeriodeAnggaranController::class, 'index'])->name('index');
        Route::get('/data', [PeriodeAnggaranController::class, 'data'])->name('data');
        Route::post('/store', [PeriodeAnggaranController::class, 'store'])->name('store');
        Route::put('/{id}/update', [PeriodeAnggaranController::class, 'update'])->name('update');
        Route::delete('/{id}/delete', [PeriodeAnggaranController::class, 'delete'])->name('delete');
    });

    Route::prefix('anggaran')->name('anggaran.')->group(function () {
        Route::get('/', [AnggaranController::class, 'index'])->name('index');
        Route::get('/data', [AnggaranController::class, 'data'])->name('data');
        Route::get('/data_full', [AnggaranController::class, 'data_full'])->name('data_full');
        Route::get('/{id}/show', [AnggaranController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AnggaranController::class, 'edit'])->name('edit');
        Route::post('/update', [AnggaranController::class, 'update'])->name('update');
    });

    Route::prefix('bilyet')->name('bilyet.')->group(function () {
        Route::get('/', [BilyetController::class, 'index'])->name('index');
    });
});
