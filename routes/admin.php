<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PrintableDocumentController;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:akses_admin'])->group(function () {

    // Printable Documents Management
    Route::prefix('printable-documents')->name('printable-documents.')->group(function () {
        Route::get('/', [PrintableDocumentController::class, 'index'])->name('index');
        Route::get('/data', [PrintableDocumentController::class, 'data'])->name('data');
        Route::put('/update', [PrintableDocumentController::class, 'updatePrintable'])->name('update');
        Route::put('/bulk-update', [PrintableDocumentController::class, 'bulkUpdatePrintable'])->name('bulk-update');
    });
});
