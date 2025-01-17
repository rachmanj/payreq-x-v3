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
        Route::get('/data', [VerificationJournalController::class, 'data'])->name('data');
        Route::get('/', [VerificationJournalController::class, 'index'])->name('index');
        Route::get('/create-journal', [VerificationJournalController::class, 'create'])->name('create');
        Route::post('/store', [VerificationJournalController::class, 'store'])->name('store');
        Route::get('/move_all_tocart', [VerificationJournalController::class, 'move_all_tocart'])->name('move_all_tocart');
        Route::get('/tocart_data', [VerificationJournalController::class, 'tocart_data'])->name('tocart_data');
        Route::get('/incart_data', [VerificationJournalController::class, 'incart_data'])->name('incart_data');
        Route::post('/add_to_cart', [VerificationJournalController::class, 'add_to_cart'])->name('add_to_cart');
        Route::post('/remove_from_cart', [VerificationJournalController::class, 'remove_from_cart'])->name('remove_from_cart');
        Route::get('/move_all_tocart', [VerificationJournalController::class, 'move_all_tocart'])->name('move_all_tocart');
        Route::get('/remove_all_fromcart', [VerificationJournalController::class, 'remove_all_fromcart'])->name('remove_all_fromcart');
        Route::get('/{id}/show', [VerificationJournalController::class, 'show'])->name('show');
        Route::get('/{id}/print', [VerificationJournalController::class, 'print'])->name('print');
        Route::delete('/{id}', [VerificationJournalController::class, 'destroy'])->name('destroy');
        Route::post('/move_selected_to_cart', [VerificationJournalController::class, 'moveSelectedToCart'])->name('move_selected_to_cart');
        Route::post('/remove_selected_from_cart', [VerificationJournalController::class, 'removeSelectedFromCart'])->name('remove_selected_from_cart');
    });
});
