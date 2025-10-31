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

    // API Keys Management
    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ApiKeyController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\Admin\ApiKeyController::class, 'data'])->name('data');
        Route::post('/', [App\Http\Controllers\Admin\ApiKeyController::class, 'store'])->name('store');
        Route::post('/{id}/activate', [App\Http\Controllers\Admin\ApiKeyController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [App\Http\Controllers\Admin\ApiKeyController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ApiKeyController::class, 'destroy'])->name('destroy');
    });
});
