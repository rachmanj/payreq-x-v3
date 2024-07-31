<?php

use App\Http\Controllers\Cashier\CashierModalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashierApprovedController;
use App\Http\Controllers\CashierOutgoingController;
use App\Http\Controllers\CashierIncomingController;
use App\Http\Controllers\CashierDashboardController;
use App\Http\Controllers\CashierGiroController;
use App\Http\Controllers\CashierGiroDetailController;
use App\Http\Controllers\CashOpnameController;
use App\Http\Controllers\Migrasi\MigrasiBucController;
use App\Http\Controllers\Migrasi\MigrasiIndexController;
use App\Http\Controllers\Migrasi\MigrasiPayreqController;

Route::prefix('cashier')->name('cashier.')->group(function () {
    // APPROVEDS PAYREQS -> ready to pay
    Route::prefix('approveds')->name('approveds.')->group(function () {
        Route::get('/data', [CashierApprovedController::class, 'data'])->name('data');
        Route::get('/', [CashierApprovedController::class, 'index'])->name('index');
        Route::put('/{id}/auto', [CashierApprovedController::class, 'auto_outgoing'])->name('auto_outgoing');
        Route::get('/{id}/pay', [CashierApprovedController::class, 'pay'])->name('pay');
        Route::put('/{id}/pay', [CashierApprovedController::class, 'store_pay'])->name('store_pay');
    });

    Route::prefix('outgoings')->name('outgoings.')->group(function () {
        Route::get('/data', [CashierOutgoingController::class, 'data'])->name('data');
        Route::get('/', [CashierOutgoingController::class, 'index'])->name('index');
        Route::get('/create', [CashierOutgoingController::class, 'create'])->name('create');
        Route::post('/store', [CashierOutgoingController::class, 'store'])->name('store');
        Route::delete('/{id}/destroy', [CashierOutgoingController::class, 'destroy'])->name('destroy');
        Route::post('/payment', [CashierOutgoingController::class, 'payment'])->name('payment');
    });

    Route::prefix('incomings')->name('incomings.')->group(function () {
        Route::get('/data', [CashierIncomingController::class, 'data'])->name('data');
        Route::get('/', [CashierIncomingController::class, 'index'])->name('index');
        Route::post('/receive', [CashierIncomingController::class, 'receive'])->name('receive');
        Route::get('/create', [CashierIncomingController::class, 'create'])->name('create');
        Route::post('/store', [CashierIncomingController::class, 'store'])->name('store');
        Route::delete('/{id}/destroy', [CashierIncomingController::class, 'destroy'])->name('destroy');

        // incoming has received
        Route::prefix('received')->name('received.')->group(function () {
            Route::get('/data', [CashierIncomingController::class, 'received_data'])->name('data');
            Route::get('/', [CashierIncomingController::class, 'received_index'])->name('index');
            Route::put('/{id}/edit_receive_date', [CashierIncomingController::class, 'edit_received_date'])->name('edit_received_date');
        });
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [CashierDashboardController::class, 'index'])->name('index');
    });

    Route::prefix('giros')->name('giros.')->group(function () {
        Route::get('/data', [CashierGiroController::class, 'data'])->name('data');
        Route::get('/{giro_id}/data', [CashierGiroDetailController::class, 'data'])->name('detail.data');
        Route::get('/{giro_id}', [CashierGiroDetailController::class, 'index'])->name('detail.index');
        Route::post('/{giro_id}/store', [CashierGiroDetailController::class, 'store'])->name('detail.store');
        Route::delete('/{giro_detail_id}/destroy', [CashierGiroDetailController::class, 'destroy'])->name('detail.destroy');
    });
    Route::resource('giros', CashierGiroController::class);

    Route::prefix('modal')->name('modal.')->group(function () {
        Route::get('/data', [CashierModalController::class, 'data'])->name('data');
        Route::post('/store', [CashierModalController::class, 'store'])->name('store');
        Route::get('/', [CashierModalController::class, 'index'])->name('index');
        Route::put('/{id}/receive', [CashierModalController::class, 'receive'])->name('receive');
    });

    Route::prefix('pcbc')->name('pcbc.')->group(function () {
        Route::get('/data', [CashOpnameController::class, 'data'])->name('data');
        Route::get('/{id}/print', [CashOpnameController::class, 'print'])->name('print');
    });
    Route::resource('pcbc', CashOpnameController::class);

    // MIGRASI
    Route::prefix('migrasi')->name('migrasi.')->group(function () {
        Route::get('/', [MigrasiIndexController::class, 'index'])->name('index');
        Route::prefix('payreqs')->name('payreqs.')->group(function () {
            Route::get('/update-status', [MigrasiPayreqController::class, 'update_status'])->name('update_status');
            Route::get('/data', [MigrasiPayreqController::class, 'data'])->name('data');
            Route::get('/', [MigrasiPayreqController::class, 'index'])->name('index');
            Route::get('/create', [MigrasiPayreqController::class, 'create'])->name('create');
            Route::post('/store', [MigrasiPayreqController::class, 'store'])->name('store');
            Route::post('/destroy', [MigrasiPayreqController::class, 'destroy'])->name('destroy');
            Route::get('/{id}', [MigrasiPayreqController::class, 'edit'])->name('edit');
            Route::put('/{id}', [MigrasiPayreqController::class, 'update'])->name('update');
            Route::put('/update_no/{id}', [MigrasiPayreqController::class, 'update_no'])->name('update_no');
        });

        // BUC
        Route::prefix('rab')->name('rab.')->group(function () {
            Route::get('/', [MigrasiBucController::class, 'index'])->name('index');
            Route::get('/exec-migrasi-rab', [MigrasiBucController::class, 'migrasi_rab'])->name('migrasi_rab');
            Route::get('/exec-realisasi-rab', [MigrasiBucController::class, 'realisasi_rab'])->name('realisasi_rab');
        });
    });
});
