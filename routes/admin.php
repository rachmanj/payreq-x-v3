<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PrintableDocumentController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\DepartmentController;

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

    // Projects Management
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::middleware('permission:projects.view')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
        });

        Route::middleware('permission:sap-sync-projects')->group(function () {
            Route::post('/sync', [ProjectController::class, 'syncFromSap'])->name('sync');
        });

        Route::middleware('permission:projects.manage-visibility')->group(function () {
            Route::patch('/{project}/visibility', [ProjectController::class, 'toggleVisibility'])->name('toggle-visibility');
        });
    });

    // Departments Management
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::middleware('permission:departments.view')->group(function () {
            Route::get('/', [DepartmentController::class, 'index'])->name('index');
        });

        Route::middleware('permission:sap-sync-departments')->group(function () {
            Route::post('/sync', [DepartmentController::class, 'syncFromSap'])->name('sync');
        });

        Route::middleware('permission:departments.manage-visibility')->group(function () {
            Route::patch('/{department}/visibility', [DepartmentController::class, 'toggleVisibility'])->name('toggle-visibility');
        });
    });

    // Business Partners Management
    Route::prefix('business-partners')->name('business-partners.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BusinessPartnerController::class, 'index'])->name('index');
        Route::post('/sync', [App\Http\Controllers\Admin\BusinessPartnerController::class, 'syncFromSap'])->name('sync');
        Route::post('/sync-customers', [App\Http\Controllers\Admin\BusinessPartnerController::class, 'syncCustomers'])->name('sync-customers');
        Route::get('/statistics', [App\Http\Controllers\Admin\BusinessPartnerController::class, 'statistics'])->name('statistics');
        Route::get('/changes', [App\Http\Controllers\Admin\BusinessPartnerController::class, 'changes'])->name('changes');
    });

    // SAP Master Data Sync
    Route::prefix('sap-master-data-sync')->name('sap-master-data-sync.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SapMasterDataSyncController::class, 'index'])->name('index');
        Route::post('/sync-all', [App\Http\Controllers\Admin\SapMasterDataSyncController::class, 'syncAll'])->name('sync-all');
        Route::post('/sync-projects', [App\Http\Controllers\Admin\SapMasterDataSyncController::class, 'syncProjects'])->name('sync-projects');
        Route::post('/sync-cost-centers', [App\Http\Controllers\Admin\SapMasterDataSyncController::class, 'syncCostCenters'])->name('sync-cost-centers');
        Route::post('/sync-accounts', [App\Http\Controllers\Admin\SapMasterDataSyncController::class, 'syncAccounts'])->name('sync-accounts');
        Route::post('/sync-business-partners', [App\Http\Controllers\Admin\SapMasterDataSyncController::class, 'syncBusinessPartners'])->name('sync-business-partners');
    });
});
