<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BucSyncController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\EquipmentSyncController;
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
use App\Http\Controllers\SearchController;
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

    // ANNOUNCEMENTS
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::put('toggle-status/{announcement}', [AnnouncementController::class, 'toggleStatus'])->name('toggle_status');
    });
    Route::resource('announcements', AnnouncementController::class);

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
        // Payreq Overdue
        Route::get('payreq', [PayreqOverdueController::class, 'index'])->name('payreq.index');
        Route::get('payreq/data', [PayreqOverdueController::class, 'data'])->name('payreq.data');
        Route::post('payreq/extend', [PayreqOverdueController::class, 'extend'])->name('payreq.extend');
        Route::post('payreq/bulk-extend', [PayreqOverdueController::class, 'bulkExtend'])->name('payreq.bulk-extend');

        // Realization Overdue
        Route::get('realization', [RealizationOverdueController::class, 'index'])->name('realization.index');
        Route::get('realization/data', [RealizationOverdueController::class, 'data'])->name('realization.data');
        Route::post('realization/extend', [RealizationOverdueController::class, 'extend'])->name('realization.extend');
        Route::post('realization/bulk-extend', [RealizationOverdueController::class, 'bulkExtend'])->name('realization.bulk-extend');
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

    //OUTGOINGS
    Route::prefix('outgoings')->name('outgoing.')->group(function () {
        Route::get('/data', [OutgoingController::class, 'data'])->name('data');
    });
    Route::resource('outgoings', OutgoingController::class);

    // ACCOUNTS
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/data', [AccountController::class, 'data'])->name('data');
        Route::post('/upload', [AccountController::class, 'upload'])->name('upload');
        Route::get('/list', [AccountController::class, 'getList'])->name('list');
        Route::get('/bank-list', [AccountController::class, 'getBankAccounts'])->name('bank_list');
    });
    Route::resource('accounts', AccountController::class);

    // DOCUMENT NUMBERING
    Route::prefix('document-number')->name('document-number.')->group(function () {
        Route::get('/data', [DocumentNumberController::class, 'data'])->name('data');
        Route::post('/auto-generate', [DocumentNumberController::class, 'auto_generate'])->name('auto_generate');
    });
    Route::resource('document-number', DocumentNumberController::class);

    Route::prefix('rabs')->name('rabs.')->group(function () {
        Route::prefix('/sync')->name('sync.')->group(function () {
            Route::get('/', [BucSyncController::class, 'index'])->name('index');
            Route::get('/rabs', [BucSyncController::class, 'sync_rabs'])->name('sync_rabs');
            Route::get('/update-rabid', [BucSyncController::class, 'update_rab'])->name('update_rab');
        });
    });

    // EQUIPMENTS SYNCHRONIZER
    Route::prefix('equipments')->name('equipments.')->group(function () {
        Route::prefix('sync')->name('sync.')->group(function () {
            Route::get('/', [EquipmentSyncController::class, 'index'])->name('index');
            Route::get('/equipments', [EquipmentSyncController::class, 'sync_equipments'])->name('sync_equipments');
        });
    });

    // SEARCH
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'index'])->name('index');
        Route::post('/display', [SearchController::class, 'display'])->name('display');
        Route::get('/show', [SearchController::class, 'show'])->name('show');
    });

    Route::get('/test', [TestController::class, 'index']);

    require __DIR__ . '/user_payreqs.php';
    require __DIR__ . '/cashier.php';
    require __DIR__ . '/approvals.php';
    require __DIR__ . '/verification.php';
    require __DIR__ . '/cash_journals.php';
    require __DIR__ . '/accounting.php';
    require __DIR__ . '/reports.php';
});

Route::post('/get_account_name', [AccountController::class, 'get_account_name'])->name('get_account_name');

// Remove or comment out this line if it exists
// Route::get('/accounts/list', [AccountController::class, 'getList'])->name('accounts.list');

Route::get('sap-sync/edit-vjdetail/data', [App\Http\Controllers\VerificationJournalController::class, 'editVjDetailData'])->name('accounting.sap-sync.edit_vjdetail_data');
