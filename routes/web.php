<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\ApprovalRequestPayreqController;
use App\Http\Controllers\ApprovalRequestRabController;
use App\Http\Controllers\ApprovalRequestRealizationController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\CashierApprovedController;
use App\Http\Controllers\CashierOutgoingController;
use App\Http\Controllers\CashJournalController;
use App\Http\Controllers\CashInJournalController;
use App\Http\Controllers\CashOutJournalController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserPayreqController;
use App\Http\Controllers\UserOngoingController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\CashierIncomingController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\PayreqAdvanceController;
use App\Http\Controllers\PayreqReimburseController;
use App\Http\Controllers\PayreqOtherController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserRealizationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VerificationJournalController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('authenticate');

    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    // Route::get('/', function () {
    //     return view('templates.dashboard');
    // });
    Route::get('/', [DashboardUserController::class, 'index']);


    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // USERS
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('data', [UserController::class, 'data'])->name('data');
        Route::put('activate/{id}', [UserController::class, 'activate'])->name('activate');
        Route::put('deactivate/{id}', [UserController::class, 'deactivate'])->name('deactivate');
        Route::put('roles-update/{id}', [UserController::class, 'roles_user_update'])->name('roles_user_update');
        Route::get('change-password/{id}', [UserController::class, 'change_password'])->name('change_password');
        Route::put('password-update/{id}', [UserController::class, 'password_update'])->name('password_update');
    });
    Route::resource('users', UserController::class);

    // ROLES
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('data', [RoleController::class, 'data'])->name('data');
    });
    Route::resource('roles', RoleController::class);

    // PERMISSIONS
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('data', [PermissionController::class, 'data'])->name('data');
    });
    Route::resource('permissions', PermissionController::class);

    // USER DASHBOARD
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardUserController::class, 'index'])->name('index');
        Route::get('/{id}', [DashboardUserController::class, 'show'])->name('show');
    });

    // USER PAYREQS
    Route::prefix('user-payreqs')->name('user-payreqs.')->group(function () {
        // ONGOINGS
        Route::prefix('ongoings')->name('ongoings.')->group(function () {
            Route::get('/', [UserOngoingController::class, 'index'])->name('index');
            Route::get('/data', [UserOngoingController::class, 'data'])->name('data');
        });

        // REALIZATION
        Route::prefix('realizations')->name('realizations.')->group(function () {
            Route::get('/data', [UserRealizationController::class, 'data'])->name('data');
            Route::get('/', [UserRealizationController::class, 'index'])->name('index');
            // add realization detials
            Route::get('/{realization_id}/add_details', [UserRealizationController::class, 'add_details'])->name('add_details');
            Route::post('/store_detail', [UserRealizationController::class, 'store_detail'])->name('store_detail');
            Route::post('/submit', [UserRealizationController::class, 'submit_realization'])->name('submit_realization');
            Route::delete('/{realization_detail_id}/delete_detail', [UserRealizationController::class, 'delete_detail'])->name('delete_detail');
            Route::get('/{realization_id}/print', [UserRealizationController::class, 'print'])->name('print');
            Route::put('/{realization_id}/void', [UserRealizationController::class, 'void'])->name('void');
            Route::delete('/{realization_id}/cancel', [UserRealizationController::class, 'cancel'])->name('cancel');
        });
        Route::resource('realizations', UserRealizationController::class);

        // PAYREQS
        Route::get('/data', [UserPayreqController::class, 'data'])->name('data');
        Route::get('/', [UserPayreqController::class, 'index'])->name('index');
        Route::get('/{id}', [UserPayreqController::class, 'show'])->name('show');
        Route::post('/cancel', [UserPayreqController::class, 'cancel'])->name('cancel');
        Route::put('/{id}', [UserPayreqController::class, 'destroy'])->name('destroy');
        // print pdf
        Route::get('/{id}/print', [UserPayreqController::class, 'print'])->name('print');

        //REIMBURSE TYPE
        Route::prefix('reimburse')->name('reimburse.')->group(function () {
            Route::get('/create', [PayreqReimburseController::class, 'create'])->name('create');
            Route::post('/submit', [PayreqReimburseController::class, 'submit_payreq'])->name('submit_payreq');
            Route::post('/store-detail', [PayreqReimburseController::class, 'store_detail'])->name('store_detail');
            Route::delete('/{realization_detail_id}/delete_detail', [PayreqReimburseController::class, 'delete_detail'])->name('delete_detail');
        });
    });

    // PAYREQ ADVANCE
    Route::resource('payreq-advance', PayreqAdvanceController::class);

    // PAYREQ OTHER
    Route::resource('payreq-reimburse', PayreqReimburseController::class);

    // CASHIER MENU
    Route::prefix('cashier')->name('cashier.')->group(function () {
        // APPROVEDS PAYREQS -> ready to pay
        Route::prefix('approveds')->name('approveds.')->group(function () {
            Route::get('/data', [CashierApprovedController::class, 'data'])->name('data');
            Route::get('/', [CashierApprovedController::class, 'index'])->name('index');
            Route::put('/{id}/auto', [CashierApprovedController::class, 'auto_outgoing'])->name('auto_outgoing');
            Route::get('/{id}/pay', [CashierApprovedController::class, 'pay'])->name('pay');
            Route::put('/{id}/pay', [CashierApprovedController::class, 'store_pay'])->name('store_pay');
        });

        Route::prefix('outgoings')->name('outgoings.')->group(function () {
            Route::get('/data', [CashierOutgoingController::class, 'data'])->name('data');
            Route::get('/', [CashierOutgoingController::class, 'index'])->name('index');
        });

        Route::prefix('incomings')->name('incomings.')->group(function () {
            Route::get('/data', [CashierIncomingController::class, 'data'])->name('data');
            Route::get('/', [CashierIncomingController::class, 'index'])->name('index');
            Route::post('/receive', [CashierIncomingController::class, 'receive'])->name('receive');
        });
    });

    // APPROVALS
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::prefix('request')->name('request.')->group(function () {
            Route::prefix('payreqs')->name('payreqs.')->group(function () {
                Route::get('/data', [ApprovalRequestPayreqController::class, 'data'])->name('data');
                Route::get('/', [ApprovalRequestPayreqController::class, 'index'])->name('index');
            });
            Route::prefix('realizations')->name('realizations.')->group(function () {
                Route::get('/data', [ApprovalRequestRealizationController::class, 'data'])->name('data');
                Route::get('/', [ApprovalRequestRealizationController::class, 'index'])->name('index');
                Route::get('/{id}', [ApprovalRequestRealizationController::class, 'show'])->name('show');
            });
            Route::prefix('rabs')->name('rabs.')->group(function () {
                Route::get('/data', [ApprovalRequestRabController::class, 'data'])->name('data');
                Route::get('/', [ApprovalRequestRabController::class, 'index'])->name('index');
            });
        });
        Route::prefix('plan')->name('plan.')->group(function () {
            Route::put('/{id}/update', [ApprovalPlanController::class, 'update'])->name('update');
        });
    });

    // CASH JOURNALS
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

    // VERIFICATIONS
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

    // JOURNALS
    Route::prefix('journals')->name('journals.')->group(function () {
        Route::get('/data', [JournalController::class, 'data'])->name('data');
        Route::get('/', [JournalController::class, 'index'])->name('index');
    });

    // GENERAL LEDGERS
    Route::prefix('general-ledgers')->name('general-ledgers.')->group(function () {
        // Route::get('/data', [GeneralLedgerController::class, 'data'])->name('data');
        Route::get('/{id}/data', [GeneralLedgerController::class, 'data'])->name('data');
        Route::get('/show/{id}', [GeneralLedgerController::class, 'show'])->name('show');
        Route::post('/search', [GeneralLedgerController::class, 'search'])->name('search');
        Route::get('/', [GeneralLedgerController::class, 'index'])->name('index');
    });

    // PARAMETERS
    Route::prefix('parameters')->name('parameters.')->group(function () {
        Route::get('/data', [ParameterController::class, 'data'])->name('data');
    });
    Route::resource('parameters', ParameterController::class);

    // APPROVAL STAGES
    Route::prefix('approval-stages')->name('approval-stages.')->group(function () {
        Route::get('/data', [ApprovalStageController::class, 'data'])->name('data');
    });
    Route::resource('approval-stages', ApprovalStageController::class);

    //OUTGOINGS
    Route::prefix('outgoings')->name('outgoing.')->group(function () {
        Route::get('/data', [OutgoingController::class, 'data'])->name('data');
    });
    Route::resource('outgoings', OutgoingController::class);

    // ACCOUNTS
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/data', [AccountController::class, 'data'])->name('data');
        Route::post('/upload', [AccountController::class, 'upload'])->name('upload');
    });
    Route::resource('accounts', AccountController::class);
});

Route::post('/get_account_name', [AccountController::class, 'get_account_name'])->name('get_account_name');
