<?php

use App\Http\Controllers\Notulen\AskController;
use App\Http\Controllers\Notulen\MeetingController;
use Illuminate\Support\Facades\Route;

Route::prefix('notulen')->name('notulen.')->group(function () {
    Route::middleware('permission:akses_notulen')->group(function () {
        Route::get('ask', [AskController::class, 'index'])->name('ask.index');
        Route::post('ask', [AskController::class, 'ask'])
            ->middleware('throttle:20,1')
            ->name('ask');

        Route::get('meetings', [MeetingController::class, 'index'])->name('meetings.index');
        Route::get('meetings/data', [MeetingController::class, 'data'])->name('meetings.data');
        Route::get('meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    });

    Route::post('meetings', [MeetingController::class, 'store'])
        ->middleware('permission:upload_notulen')
        ->name('meetings.store');

    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])
        ->middleware('permission:delete_notulen')
        ->name('meetings.destroy');

    Route::post('meetings/{meeting}/reprocess', [MeetingController::class, 'reprocess'])
        ->middleware('permission:upload_notulen')
        ->name('meetings.reprocess');
});
