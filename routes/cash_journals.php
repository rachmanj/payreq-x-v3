<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashJournalController;
use App\Http\Controllers\CashInJournalController;
use App\Http\Controllers\CashOutJournalController;

Route::prefix('cash-journals')->name('cash-journals.')->group(function () {
    Route::prefix('out')->name('out.')->group(function () {
        Route::get('/create', [CashOutJournalController::class, 'create'])->name('create');
        Route::post('/store', [CashOutJournalController::class, 'store'])->name('store');
        Route::get('/to_cart/data', [CashOutJournalController::class, 'to_cart_data'])->name('to_cart.data');
        Route::get('/in_cart/data', [CashOutJournalController::class, 'in_cart_data'])->name('in_cart.data');
        Route::get('/in_cart', [CashOutJournalController::class, 'in_cart'])->name('in_cart');
        Route::post('/add_to_cart', [CashOutJournalController::class, 'add_to_cart'])->name('add_to_cart');
        Route::post('/remove_from_cart', [CashOutJournalController::class, 'remove_from_cart'])->name('remove_from_cart');
        Route::get('/move_all_tocart', [CashOutJournalController::class, 'move_all_tocart'])->name('move_all_tocart');
        Route::get('/remove_all_fromcart', [CashOutJournalController::class, 'remove_all_fromcart'])->name('remove_all_fromcart');
    });

    Route::prefix('in')->name('in.')->group(function () {
        Route::get('/create', [CashInJournalController::class, 'create'])->name('create');
        Route::post('/store', [CashInJournalController::class, 'store'])->name('store');
        Route::get('/to_cart/data', [CashInJournalController::class, 'to_cart_data'])->name('to_cart.data');
        Route::get('/in_cart/data', [CashInJournalController::class, 'in_cart_data'])->name('in_cart.data');
        Route::get('/in_cart', [CashInJournalController::class, 'in_cart'])->name('in_cart');
        Route::post('/add_to_cart', [CashInJournalController::class, 'add_to_cart'])->name('add_to_cart');
        Route::post('/remove_from_cart', [CashInJournalController::class, 'remove_from_cart'])->name('remove_from_cart');
        Route::get('/move_all_tocart', [CashInJournalController::class, 'move_all_tocart'])->name('move_all_tocart');
        Route::get('/remove_all_fromcart', [CashInJournalController::class, 'remove_all_fromcart'])->name('remove_all_fromcart');
    });

    Route::get('/data', [CashJournalController::class, 'data'])->name('data');
    Route::get('/', [CashJournalController::class, 'index'])->name('index');
    Route::get('/print/{id}', [CashJournalController::class, 'print'])->name('print');
    Route::get('/show/{id}', [CashJournalController::class, 'show'])->name('show');
    Route::get('/{outgoing_id}/delete_detail', [CashJournalController::class, 'delete_detail'])->name('delete_detail');
    Route::post('/update_sap', [CashJournalController::class, 'update_sap'])->name('update_sap');
    Route::post('/cancel_sap_info', [CashJournalController::class, 'cancel_sap_info'])->name('cancel_sap_info');
    Route::delete('/{id}', [CashJournalController::class, 'destroy'])->name('destroy');
});
