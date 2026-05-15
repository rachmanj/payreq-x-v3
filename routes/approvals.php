<?php

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\ApprovalRequestAnggaranController;
use App\Http\Controllers\ApprovalRequestPayreqController;
use App\Http\Controllers\ApprovalRequestRealizationController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

Route::prefix('approvals')->name('approvals.')->group(function () {
    Route::prefix('request')->name('request.')->group(function () {
        Route::get('/document-count', [ToolController::class, 'approval_documents_count_api'])->name('document-count');

        Route::prefix('payreqs')->name('payreqs.')->group(function () {
            Route::get('/data', [ApprovalRequestPayreqController::class, 'data'])->name('data'); // 'approvals.request.payreqs.data'
            Route::get('/', [ApprovalRequestPayreqController::class, 'index'])->name('index'); // 'approvals.request.payreqs.index'
            Route::get('/{id}', [ApprovalRequestPayreqController::class, 'show'])->name('show'); // 'approvals.request.payreqs.show'
            Route::put('/{id}/details', [ApprovalRequestPayreqController::class, 'updateDetails'])->name('update-details');
        });
        Route::prefix('realizations')->name('realizations.')->group(function () {
            Route::get('/data', [ApprovalRequestRealizationController::class, 'data'])->name('data');
            Route::get('/', [ApprovalRequestRealizationController::class, 'index'])->name('index');
            Route::get('/{id}', [ApprovalRequestRealizationController::class, 'show'])->name('show');
            Route::put('/{id}/details', [ApprovalRequestRealizationController::class, 'updateDetails'])->name('update-details');
        });
        Route::prefix('anggarans')->name('anggarans.')->group(function () {
            Route::get('/data', [ApprovalRequestAnggaranController::class, 'data'])->name('data');
            Route::get('/', [ApprovalRequestAnggaranController::class, 'index'])->name('index');
        });
    });
    Route::prefix('plan')->name('plan.')->group(function () {
        Route::get('/requestor-replies', [ApprovalPlanController::class, 'requestorRepliesInbox'])->name('requestor-replies.inbox');
        Route::put('/{id}/requestor-replies/mark-read', [ApprovalPlanController::class, 'markRequestorReplyRead'])->name('requestor-replies.mark-read');
        Route::get('/{id}/conversation', [ApprovalPlanController::class, 'conversation'])->name('conversation');
        Route::put('/{id}/update', [ApprovalPlanController::class, 'update'])->name('update');
        Route::put('/{id}/requestor-remarks', [ApprovalPlanController::class, 'updateRequestorRemarks'])->name('requestor-remarks.update');
        Route::post('/bulk-approve', [ApprovalPlanController::class, 'bulkApprove'])->name('bulk-approve');
    });
});

// APPROVAL STAGES
Route::prefix('approval-stages')->name('approval-stages.')->group(function () {
    Route::get('/data', [ApprovalStageController::class, 'data'])->name('data');
    Route::post('/auto-generate', [ApprovalStageController::class, 'auto_generate'])->name('auto_generate');
});
Route::resource('approval-stages', ApprovalStageController::class);
