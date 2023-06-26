<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdvanceCategoryController;
use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\ApprovalStageController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardAccountingController;
use App\Http\Controllers\DashboardDncController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GiroController;
use App\Http\Controllers\GiroDetailController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MyPayreqController;
use App\Http\Controllers\OngoingController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\PayreqAdvanceController;
use App\Http\Controllers\PayreqOtherController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RabController;
use App\Http\Controllers\RealizationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('authenticate');

    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('templates.dashboard');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // USERS
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('data', [UserController::class, 'data'])->name('data');
        Route::put('activate/{id}', [UserController::class, 'activate'])->name('activate');
        Route::put('deactivate/{id}', [UserController::class, 'deactivate'])->name('deactivate');
        Route::put('roles-update/{id}', [UserController::class, 'roles_user_update'])->name('roles_user_update');
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

    // MY PAYREQS
    Route::prefix('mypayreqs')->name('mypayreqs.')->group(function () {
        Route::get('/data', [MyPayreqController::class, 'data'])->name('data');
        Route::get('/', [MyPayreqController::class, 'index'])->name('index');
        Route::get('/{id}', [MyPayreqController::class, 'show'])->name('show');
        Route::delete('/{id}', [MyPayreqController::class, 'destroy'])->name('destroy');
        // print pdf
        Route::get('/{id}/print', [MyPayreqController::class, 'print'])->name('print');
    });

    // PAYREQ ADVANCE
    Route::resource('payreq-advance', PayreqAdvanceController::class);

    // PAYREQ OTHER
    Route::resource('payreq-other', PayreqOtherController::class);

    // APPROVALS
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::prefix('request')->name('request.')->group(function () {
            Route::get('/data', [ApprovalRequestController::class, 'data'])->name('data');
            Route::get('/', [ApprovalRequestController::class, 'index'])->name('index');
        });
        Route::prefix('plan')->name('plan.')->group(function () {
            Route::put('/{id}/update', [ApprovalPlanController::class, 'update'])->name('update');
        });
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

    // OUTGOINGS
    Route::prefix('outgoings')->name('outgoings.')->group(function () {
        Route::get('/data', [OutgoingController::class, 'data'])->name('data');
        Route::get('/', [OutgoingController::class, 'index'])->name('index');
        Route::post('/store', [OutgoingController::class, 'store'])->name('store');
        Route::get('/{payreq_id}', [OutgoingController::class, 'quick'])->name('quick');
    });

    // ONGOINGS
    Route::prefix('ongoings')->name('ongoings.')->group(function () {
        Route::get('/data', [OngoingController::class, 'data'])->name('data');
        Route::get('/', [OngoingController::class, 'index'])->name('index');
    });

    // ACCOUNTS
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/data', [AccountController::class, 'data'])->name('data');
        Route::post('/upload', [AccountController::class, 'upload'])->name('upload');
    });
    Route::resource('accounts', AccountController::class);

    // REALIZATION
    Route::prefix('realization')->name('realization.')->group(function () {
        Route::get('/data', [RealizationController::class, 'data'])->name('data');
        Route::get('/', [RealizationController::class, 'index'])->name('index');
        Route::put('/{id}', [RealizationController::class, 'update'])->name('update');
    });



    // VERIFICATION
    Route::prefix('verify')->name('verify.')->group(function () {
        Route::get('/data', [VerifyController::class, 'data'])->name('data');
        Route::get('/', [VerifyController::class, 'index'])->name('index');
        Route::put('/{id}', [VerifyController::class, 'update'])->name('update');
    });

    // SEARCH
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'index'])->name('index');
        Route::post('/display', [SearchController::class, 'display'])->name('display');
        Route::get('/{id}/edit', [SearchController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SearchController::class, 'update'])->name('update');
        Route::delete('/{id}', [SearchController::class, 'destroy'])->name('destroy');
    });

    //RAB
    Route::prefix('rabs')->name('rabs.')->group(function () {
        Route::get('/data', [RabController::class, 'data'])->name('data');
        Route::get('/{rab_id}/data', [RabController::class, 'payreq_data'])->name('payreq_data');
        Route::put('/{id}/status', [RabController::class, 'update_status'])->name('update_status');
        Route::get('/{id}/test', [RabController::class, 'test'])->name('test');
    });
    Route::resource('rabs', RabController::class);

    // TRANSAKSIS
    Route::get('transaksi/data', [TransaksiController::class, 'data'])->name('transaksi.data');
    Route::resource('transaksi', TransaksiController::class);

    // ACCOUNT
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/data', [AccountController::class, 'data'])->name('data');
        Route::post('/transaksi-store', [AccountController::class, 'transaksi_store'])->name('transaksi_store');
    });
    Route::resource('account', AccountController::class);

    // REKAPS
    Route::prefix('rekaps')->name('rekaps.')->group(function () {
        Route::get('/data', [RekapController::class, 'data'])->name('data');
        Route::get('/', [RekapController::class, 'index'])->name('index');
        Route::delete('/{id}', [RekapController::class, 'destroy'])->name('destroy');
        Route::get('/export', [RekapController::class, 'export'])->name('export');
    });

    // BUDGET
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::get('/just_updated', [BudgetController::class, 'just_updated'])->name('just_updated');
        Route::put('/{id}', [BudgetController::class, 'update'])->name('update');
        Route::get('/data', [BudgetController::class, 'data'])->name('data');
        Route::get('/just_updated/data', [BudgetController::class, 'just_updated_data'])->name('just_updated_data');
    });

    // ADVANCE CATEGORY
    Route::get('adv-category/data', [AdvanceCategoryController::class, 'data'])->name('adv-category.data');
    Route::resource('adv-category', AdvanceCategoryController::class);

    // ACC-DASHBOARD
    Route::prefix('acc-dashboard')->name('acc-dashboard.')->group(function () {
        Route::get('/', [DashboardAccountingController::class, 'index'])->name('index');
        Route::get('test', [DashboardAccountingController::class, 'test'])->name('test');
    });

    // DNC-DASHBOARD
    Route::prefix('dnc-dashboard')->name('dnc-dashboard.')->group(function () {
        Route::get('/', [DashboardDncController::class, 'index'])->name('index');
        Route::get('test', [DashboardDncController::class, 'test'])->name('test');
    });

    //EMAILS
    Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/data', [EmailController::class, 'data'])->name('data');
        Route::get('/', [EmailController::class, 'index'])->name('index');
        Route::get('/push/{id}', [EmailController::class, 'push'])->name('push');
    });

    //GIROS
    Route::prefix('giros')->name('giros.')->group(function () {
        Route::get('/data', [GiroController::class, 'data'])->name('data');
        Route::get('/{giro_id}/data', [GiroDetailController::class, 'data'])->name('detail.data');
        Route::get('/{giro_id}', [GiroDetailController::class, 'index'])->name('detail.index');
        Route::post('/{giro_id}/store', [GiroDetailController::class, 'store'])->name('detail.store');
        Route::delete('/{giro_detail_id}/destroy', [GiroDetailController::class, 'destroy'])->name('detail.destroy');
    });
    Route::resource('giros', GiroController::class);

    // INVOICES
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/data', [InvoiceController::class, 'data'])->name('data');
        Route::get('/paid_data', [InvoiceController::class, 'paid_data'])->name('paid_data');
        Route::put('/{id}/paid', [InvoiceController::class, 'paid'])->name('paid');
        Route::get('/paid-index', [InvoiceController::class, 'paid_index'])->name('paid.index');
        Route::post('/multi-paid', [InvoiceController::class, 'multi_paid'])->name('multi_paid');
    });
    Route::resource('invoices', InvoiceController::class);
});
