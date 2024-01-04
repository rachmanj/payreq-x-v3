<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PayreqOverdueController;
use App\Http\Controllers\RealizationOverdueController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TestController;
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

    // CASHIER MENU

    // APPROVALS

    // CASH JOURNALS

    // VERIFICATIONS

    // USERS OVERDUE
    Route::prefix('document-overdue')->name('document-overdue.')->group(function () {
        // payreq
        Route::prefix('payreq')->name('payreq.')->group(function () {
            Route::get('/data', [PayreqOverdueController::class, 'data'])->name('data');
            Route::get('/', [PayreqOverdueController::class, 'index'])->name('index');
            Route::post('/extend', [PayreqOverdueController::class, 'extend'])->name('extend');
        });

        // realizations
        Route::prefix('realization')->name('realization.')->group(function () {
            Route::get('/data', [RealizationOverdueController::class, 'data'])->name('data');
            Route::get('/', [RealizationOverdueController::class, 'index'])->name('index');
            Route::post('/extend', [RealizationOverdueController::class, 'extend'])->name('extend');
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
        Route::post('/auto-generate', [ApprovalStageController::class, 'auto_generate'])->name('auto_generate');
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

    // DOCUMENT NUMBERING
    Route::prefix('document-number')->name('document-number.')->group(function () {
        Route::get('/data', [DocumentNumberController::class, 'data'])->name('data');
        Route::post('/auto-generate', [DocumentNumberController::class, 'auto_generate'])->name('auto_generate');
    });
    Route::resource('document-number', DocumentNumberController::class);

    Route::get('/test', [TestController::class, 'index']);

    require __DIR__ . '/user_payreqs.php';
    require __DIR__ . '/cashier.php';
    require __DIR__ . '/approvals.php';
    require __DIR__ . '/verification.php';
    require __DIR__ . '/cash_journals.php';
    require __DIR__ . '/accounting.php';
});

Route::post('/get_account_name', [AccountController::class, 'get_account_name'])->name('get_account_name');
