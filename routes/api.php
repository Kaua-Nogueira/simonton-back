<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CostCenterController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EbdController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
// Debug Route for Session Config
Route::get('/debug-session', function() {
    return response()->json([
        'session_config' => config('session'),
        'cors_config' => config('cors'),
        'env_same_site' => env('SESSION_SAME_SITE'),
        'cookies' => request()->cookies->all(),
    ]);
});
// Public PDF Route for direct browser access
Route::get('meetings/{meeting}/pdf', [\App\Http\Controllers\Api\MeetingController::class, 'pdf']);

// Protected Routes
Route::middleware(['auth:sanctum', 'acl'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
    Route::put('/user/password', [AuthController::class, 'updatePassword'])->name('auth.password.update');

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    // Transactions
    Route::group(['as' => 'transactions.'], function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('index');
        Route::get('/transactions/pending', [TransactionController::class, 'pending'])->name('pending');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('store');
        Route::post('/transactions/{transaction}/confirm', [TransactionController::class, 'confirm'])->name('confirm');
        Route::post('/transactions/{transaction}/split', [TransactionController::class, 'split'])->name('split');
        Route::post('/transactions/import', [TransactionController::class, 'importOFX'])->name('import');
    });

    // Members
    Route::group(['as' => 'members.'], function () {
        Route::get('/members', [MemberController::class, 'index'])->name('index');
        Route::get('/members/{member}', [MemberController::class, 'show'])->name('show');
        Route::post('/members', [MemberController::class, 'store'])->name('store');
        Route::patch('/members/{member}', [MemberController::class, 'update'])->name('update');
        Route::get('/members/{member}/contributions', [MemberController::class, 'contributions'])->name('contributions');
        Route::post('/members/{member}/roles', [RoleController::class, 'assignRole'])->name('roles.assign');
        Route::delete('/members/{member}/roles/{role}', [RoleController::class, 'deleteAssignment'])->name('roles.revoke');
        Route::get('/members/{member}/roles', [RoleController::class, 'getHistory'])->name('roles.history');
        Route::get('/members/{member}/transfer-letter', [MemberController::class, 'transferLetter'])->name('transfer-letter');
        
        // Member System Access
        Route::post('/members/{member}/user', [App\Http\Controllers\MemberUserController::class, 'store'])->name('user.store');
        Route::put('/members/{member}/user', [App\Http\Controllers\MemberUserController::class, 'update'])->name('user.update');
        Route::delete('/members/{member}/user', [App\Http\Controllers\MemberUserController::class, 'destroy'])->name('user.destroy');
    });

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Cost Centers
    Route::apiResource('cost-centers', CostCenterController::class);

    // Cash Register
    Route::get('/cash-register', [CashRegisterController::class, 'index'])->name('cash-register.index');
    Route::get('/cash-register/balance', [CashRegisterController::class, 'currentBalance'])->name('cash-register.balance');

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // EBD (Escola Dominical)
    Route::get('/ebd/classes', [EbdController::class, 'index'])->name('ebd.classes.index');
    Route::get('/ebd/classes/{class}', [EbdController::class, 'show'])->name('ebd.classes.show');
    Route::post('/ebd/classes/{class}/attendance', [EbdController::class, 'storeAttendance'])->name('ebd.classes.attendance');

    // Reports
    Route::get('/reports/{type}', [ReportController::class, 'show'])->name('reports.view');

    // Secretariat (Atas & Resoluções)
    Route::apiResource('meetings', \App\Http\Controllers\Api\MeetingController::class);
    Route::post('meetings/{meeting}/populate', [\App\Http\Controllers\Api\MeetingController::class, 'populateAttendance'])->name('meetings.populate');
    Route::apiResource('resolutions', \App\Http\Controllers\Api\ResolutionController::class);

    // Internal Societies
    Route::apiResource('societies', \App\Http\Controllers\Api\SocietyController::class);
    Route::apiResource('societies.members', \App\Http\Controllers\Api\SocietyMemberController::class);
    Route::apiResource('societies.mandates', \App\Http\Controllers\Api\SocietyMandateController::class);
    Route::post('societies/{society}/mandates/{mandate}/roles', [\App\Http\Controllers\Api\SocietyMandateController::class, 'addRole'])->name('societies.mandates.roles.add');
    Route::delete('societies/{society}/mandates/{mandate}/roles/{role}', [\App\Http\Controllers\Api\SocietyMandateController::class, 'removeRole'])->name('societies.mandates.roles.remove');

    Route::get('societies/{society}/financial', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'index'])->name('societies.financial.index');
    Route::post('societies/{society}/financial/movements', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'storeMovement'])->name('societies.financial.movements');
    Route::get('societies/{society}/financial/dues', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'getDuesGrid'])->name('societies.financial.dues');
    Route::post('societies/{society}/financial/dues', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'payDues'])->name('societies.financial.pay-dues');

    // Patrimony & Janitorial
    Route::prefix('patrimony')
    ->as('patrimony.')
    ->group(function () {
        Route::apiResource('locations', \App\Http\Controllers\Api\Patrimony\LocationController::class);
        Route::apiResource('categories', \App\Http\Controllers\Api\Patrimony\CategoryController::class);
        Route::apiResource('assets', \App\Http\Controllers\Api\Patrimony\AssetController::class);
        // Let's use custom routes for clarity since MaintenanceController handles both Requests and Schedules
        Route::get('maintenance/requests', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'indexRequests'])->name('maintenance.requests.index');
        Route::post('maintenance/requests', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'storeRequest'])->name('maintenance.requests.store');
        Route::get('maintenance/requests/{maintenance}', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'showRequest'])->name('maintenance.requests.show');
        Route::put('maintenance/requests/{maintenance}', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'updateRequest'])->name('maintenance.requests.update');
        
        Route::get('maintenance/schedules', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'indexSchedules'])->name('maintenance.schedules.index');
        Route::post('maintenance/schedules', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'storeSchedule'])->name('maintenance.schedules.store');

        Route::get('loans', [\App\Http\Controllers\Api\Patrimony\LoanController::class, 'index'])->name('loans.index');
        Route::post('loans', [\App\Http\Controllers\Api\Patrimony\LoanController::class, 'store'])->name('loans.store');
        Route::post('loans/{loan}/return', [\App\Http\Controllers\Api\Patrimony\LoanController::class, 'returnLoan'])->name('loans.return');

        Route::get('spaces/bookings', [\App\Http\Controllers\Api\Patrimony\SpaceController::class, 'index'])->name('spaces.bookings.index');
        Route::post('spaces/bookings', [\App\Http\Controllers\Api\Patrimony\SpaceController::class, 'store'])->name('spaces.bookings.store');
        Route::post('spaces/bookings/{booking}/status', [\App\Http\Controllers\Api\Patrimony\SpaceController::class, 'updateStatus'])->name('spaces.bookings.status');

        Route::apiResource('consumables', \App\Http\Controllers\Api\Patrimony\ConsumableController::class);
    });

    // Finance - Budgets & Obligations
    Route::prefix('finance')->as('finance.')->group(function () {
        Route::get('budgets/{budget}/status', [\App\Http\Controllers\Api\BudgetController::class, 'status'])->name('budgets.status');
        Route::get('budgets/{budget}/items', [\App\Http\Controllers\Api\BudgetController::class, 'items'])->name('budgets.items');
        Route::post('budgets/{budget}/items', [\App\Http\Controllers\Api\BudgetController::class, 'storeItem'])->name('budgets.items.store');
        Route::post('budgets/{budget}/movements', [\App\Http\Controllers\Api\BudgetController::class, 'storeMovement'])->name('budgets.movements.store');
        Route::apiResource('budgets', \App\Http\Controllers\Api\BudgetController::class);

        Route::get('remittances/preview', [\App\Http\Controllers\Api\RemittanceController::class, 'preview'])->name('remittances.preview');
        Route::post('remittances/generate', [\App\Http\Controllers\Api\RemittanceController::class, 'generate'])->name('remittances.generate');
        Route::apiResource('remittances', \App\Http\Controllers\Api\RemittanceController::class)->only(['index', 'show']);
    });

    // ACL / RBAC
    Route::prefix('acl')->as('acl.')->group(function () {
        // Permissions
        Route::get('permissions', [\App\Http\Controllers\Api\Acl\PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions/scan', [\App\Http\Controllers\Api\Acl\PermissionController::class, 'scan'])->name('permissions.scan');

        // Roles
        Route::apiResource('roles', \App\Http\Controllers\Api\Acl\RoleController::class);

        // Users
        Route::get('users', [\App\Http\Controllers\Api\Acl\UserController::class, 'index'])->name('users.index');
        Route::put('users/{user}', [\App\Http\Controllers\Api\Acl\UserController::class, 'update'])->name('users.update');

        // Menus
        Route::post('menus/reorder', [\App\Http\Controllers\Api\Acl\MenuController::class, 'reorder'])->name('menus.reorder');
        Route::apiResource('menus', \App\Http\Controllers\Api\Acl\MenuController::class);
    });
});
