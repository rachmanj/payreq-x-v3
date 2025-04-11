<?php

use App\Http\Controllers\Cashier\BilyetController;
use App\Http\Controllers\Cashier\BilyetTempController;
use App\Http\Controllers\Cashier\CashierDokumenController;
use App\Http\Controllers\Cashier\CashierModalController;
use App\Http\Controllers\Cashier\KoranController;
use App\Http\Controllers\Cashier\PcbcController;
use App\Http\Controllers\Cashier\TransaksiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashierApprovedController;
use App\Http\Controllers\CashierOutgoingController;
use App\Http\Controllers\CashierIncomingController;
use App\Http\Controllers\CashierDashboardController;
use App\Http\Controllers\CashOpnameController;
use App\Http\Controllers\Migrasi\MigrasiBucController;
use App\Http\Controllers\Migrasi\MigrasiIndexController;
use App\Http\Controllers\Migrasi\MigrasiPayreqController;
use App\Http\Controllers\Cashier\SapTransactionController;

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

    Route::prefix('modal')->name('modal.')->group(function () {
        Route::get('/data', [CashierModalController::class, 'data'])->name('data');
        Route::post('/store', [CashierModalController::class, 'store'])->name('store');
        Route::get('/', [CashierModalController::class, 'index'])->name('index');
        Route::put('/{id}/receive', [CashierModalController::class, 'receive'])->name('receive');
    });

    // PCBC
    Route::prefix('pcbc')->name('pcbc.')->group(function () {
        Route::get('/data', [PcbcController::class, 'data'])->name('data');
        Route::get('/your-data', [PcbcController::class, 'your_data'])->name('your_data');
        Route::get('/{id}/print', [PcbcController::class, 'print'])->name('print');
        Route::put('/{id}/update-pcbc', [PcbcController::class, 'update_pcbc'])->name('update_pcbc');
        Route::delete('/{id}/destroy-pcbc', [PcbcController::class, 'destroy_pcbc'])->name('destroy_pcbc');
        Route::post('/upload', [PcbcController::class, 'upload'])->name('upload');
        Route::resource('/', PcbcController::class)->parameters(['' => 'pcbc']);
    });

    // REKENING KORAN
    Route::prefix('koran')->name('koran.')->group(function () {
        Route::get('/data', [KoranController::class, 'data'])->name('data');
        Route::get('/', [KoranController::class, 'index'])->name('index');
        Route::post('/upload', [KoranController::class, 'upload'])->name('upload');
    });

    // TRANSAKSIS
    Route::prefix('transaksis')->name('transaksis.')->group(function () {
        Route::get('/data', [TransaksiController::class, 'data'])->name('data');
        Route::get('/', [TransaksiController::class, 'index'])->name('index');
        Route::post('/store', [TransaksiController::class, 'store'])->name('store');
    });

    // BILYET
    Route::prefix('bilyets')->name('bilyets.')->group(function () {
        Route::get('data', [BilyetController::class, 'data'])->name('data');
        Route::get('/', [BilyetController::class, 'index'])->name('index');
        Route::post('/', [BilyetController::class, 'store'])->name('store');
        Route::get('release', [BilyetController::class, 'release_index'])->name('release_index');
        Route::get('cair', [BilyetController::class, 'cair_index'])->name('cair_index');
        Route::get('void', [BilyetController::class, 'void_index'])->name('void_index');
        Route::put('{id}', [BilyetController::class, 'update'])->name('update');
        Route::put('{id}/release', [BilyetController::class, 'release'])->name('release');
        Route::get('export', [BilyetController::class, 'export'])->name('export');
        Route::post('import', [BilyetController::class, 'import'])->name('import'); // move data from bilyet_temp to bilyet
        Route::delete('{id}', [BilyetController::class, 'destroy'])->name('destroy');
        Route::post('update-many', [BilyetController::class, 'update_many'])->name('update_many');
    });

    // BILYET TEMP
    Route::prefix('bilyet-temps')->name('bilyet-temps.')->group(function () {
        Route::get('data', [BilyetTempController::class, 'data'])->name('data');
        Route::get('/', [BilyetTempController::class, 'index'])->name('index');
        Route::post('upload', [BilyetTempController::class, 'upload'])->name('upload');
        Route::get('truncate', [BilyetTempController::class, 'truncate'])->name('truncate');
        Route::get('/{id}/destroy', [BilyetTempController::class, 'destroy'])->name('destroy');
        Route::put('{id}', [BilyetTempController::class, 'update'])->name('update');
    });

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

    // DOKUMEN
    Route::prefix('dokumen')->name('dokumen.')->group(function () {
        Route::get('/data', [CashierDokumenController::class, 'data'])->name('data');
        Route::get('/', [CashierDokumenController::class, 'index'])->name('index');
        Route::post('/upload', [CashierDokumenController::class, 'upload'])->name('upload');
        Route::delete('/{id}', [CashierDokumenController::class, 'destroy'])->name('destroy');
        Route::put('/{id}', [CashierDokumenController::class, 'update'])->name('update');
    });

    Route::prefix('sap-transactions')->name('sap-transactions.')->group(function () {
        Route::get('/', [SapTransactionController::class, 'index'])->name('index');
        Route::post('/data', [SapTransactionController::class, 'data'])->name('data');
    });
});
