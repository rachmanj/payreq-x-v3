<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VerificationJournalController;

Route::prefix('verifications')->name('verifications.')->group(function () {
    Route::get('/data', [VerificationController::class, 'data'])->name('data');
    Route::get('/', [VerificationController::class, 'index'])->name('index');
    Route::post('/', [VerificationController::class, 'store'])->name('store');
    Route::get('/{id}/create', [VerificationController::class, 'create'])->name('create');
    Route::get('/{id}/edit', [VerificationController::class, 'edit'])->name('edit');
    Route::post('/save', [VerificationController::class, 'save'])->name('save');

    // journal
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/', [VerificationJournalController::class, 'index'])->name('index');
        Route::get('/journal_create', [VerificationJournalController::class, 'create'])->name('create');
        Route::post('/store', [VerificationJournalController::class, 'store'])->name('store');
        Route::get('/move_all_tocart', [VerificationJournalController::class, 'move_all_tocart'])->name('move_all_tocart');
        Route::get('/tocart_data', [VerificationJournalController::class, 'tocart_data'])->name('tocart_data');
        Route::get('/incart_data', [VerificationJournalController::class, 'incart_data'])->name('incart_data');
        Route::post('/add_to_cart', [VerificationJournalController::class, 'add_to_cart'])->name('add_to_cart');
        Route::post('/remove_from_cart', [VerificationJournalController::class, 'remove_from_cart'])->name('remove_from_cart');
        Route::get('/move_all_tocart', [VerificationJournalController::class, 'move_all_tocart'])->name('move_all_tocart');
        Route::get('/remove_all_fromcart', [VerificationJournalController::class, 'remove_all_fromcart'])->name('remove_all_fromcart');
    });
});
