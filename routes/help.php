<?php

use App\Http\Controllers\Help\HelpController;
use Illuminate\Support\Facades\Route;

Route::prefix('help')->name('help.')->middleware(['permission:akses_help', 'throttle:30,1'])->group(function () {
    Route::post('ask', [HelpController::class, 'ask'])->name('ask');
    Route::post('feedback', [HelpController::class, 'feedback'])->name('feedback');
});
