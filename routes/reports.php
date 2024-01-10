<?php

use App\Http\Controllers\Reports\EquipmentController;
use App\Http\Controllers\Reports\OngoingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reports\ReportIndexController;

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportIndexController::class, 'index'])->name('index');

    Route::prefix('ongoing')->name('ongoing.')->group(function () {
        Route::get('/', [OngoingController::class, 'index'])->name('index');
        Route::get('/data', [OngoingController::class, 'data'])->name('data');
        Route::get('/{int}/project', [OngoingController::class, 'project_index'])->name('project');
    });

    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::get('/', [EquipmentController::class, 'index'])->name('index');
        Route::get('/data', [EquipmentController::class, 'data'])->name('data');
        // add route with query of unit_no
        Route::get('/unit_no', [EquipmentController::class, 'detail'])->name('detail');
    });
});
