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
// Public PDF Route for direct browser access
Route::get('meetings/{meeting}/pdf', [\App\Http\Controllers\Api\MeetingController::class, 'pdf']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/pending', [TransactionController::class, 'pending']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/{transaction}/confirm', [TransactionController::class, 'confirm']);
    Route::post('/transactions/{transaction}/split', [TransactionController::class, 'split']);
    Route::post('/transactions/import', [TransactionController::class, 'importOFX']);

    // Members
    Route::get('/members', [MemberController::class, 'index']);
    Route::get('/members/{member}', [MemberController::class, 'show']);
    Route::post('/members', [MemberController::class, 'store']);
    Route::patch('/members/{member}', [MemberController::class, 'update']);
    Route::get('/members/{member}/contributions', [MemberController::class, 'contributions']);
    Route::post('/members/{member}/roles', [RoleController::class, 'assignRole']);
    Route::delete('/members/{member}/roles/{role}', [RoleController::class, 'deleteAssignment']);
    Route::get('/members/{member}/roles', [RoleController::class, 'getHistory']);
    Route::get('/members/{member}/transfer-letter', [MemberController::class, 'transferLetter']);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Cost Centers
    Route::apiResource('cost-centers', CostCenterController::class);

    // Cash Register
    Route::get('/cash-register', [CashRegisterController::class, 'index']);
    Route::get('/cash-register/balance', [CashRegisterController::class, 'currentBalance']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // EBD (Escola Dominical)
    Route::get('/ebd/classes', [EbdController::class, 'index']);
    Route::get('/ebd/classes/{class}', [EbdController::class, 'show']);
    Route::post('/ebd/classes/{class}/attendance', [EbdController::class, 'storeAttendance']);

    // Reports
    Route::get('/reports/{type}', [ReportController::class, 'show']);

    // Secretariat (Atas & Resoluções)
    Route::apiResource('meetings', \App\Http\Controllers\Api\MeetingController::class);
    Route::post('meetings/{meeting}/populate', [\App\Http\Controllers\Api\MeetingController::class, 'populateAttendance']);
    Route::apiResource('resolutions', \App\Http\Controllers\Api\ResolutionController::class);

    // Internal Societies
    Route::apiResource('societies', \App\Http\Controllers\Api\SocietyController::class);
    Route::apiResource('societies.members', \App\Http\Controllers\Api\SocietyMemberController::class);
    Route::apiResource('societies.mandates', \App\Http\Controllers\Api\SocietyMandateController::class);
    Route::post('societies/{society}/mandates/{mandate}/roles', [\App\Http\Controllers\Api\SocietyMandateController::class, 'addRole']);
    Route::delete('societies/{society}/mandates/{mandate}/roles/{role}', [\App\Http\Controllers\Api\SocietyMandateController::class, 'removeRole']);

    Route::get('societies/{society}/financial', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'index']);
    Route::post('societies/{society}/financial/movements', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'storeMovement']);
    Route::get('societies/{society}/financial/dues', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'getDuesGrid']);
    Route::post('societies/{society}/financial/dues', [\App\Http\Controllers\Api\SocietyFinancialController::class, 'payDues']);

    // Patrimony & Janitorial
    Route::prefix('patrimony')->group(function () {
        Route::apiResource('locations', \App\Http\Controllers\Api\Patrimony\LocationController::class);
        Route::apiResource('categories', \App\Http\Controllers\Api\Patrimony\CategoryController::class);
        Route::apiResource('assets', \App\Http\Controllers\Api\Patrimony\AssetController::class);
        // Let's use custom routes for clarity since MaintenanceController handles both Requests and Schedules
        Route::get('maintenance/requests', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'indexRequests']);
        Route::post('maintenance/requests', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'storeRequest']);
        Route::get('maintenance/requests/{maintenance}', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'showRequest']);
        Route::put('maintenance/requests/{maintenance}', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'updateRequest']);
        
        Route::get('maintenance/schedules', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'indexSchedules']);
        Route::post('maintenance/schedules', [\App\Http\Controllers\Api\Patrimony\MaintenanceController::class, 'storeSchedule']);

        Route::get('loans', [\App\Http\Controllers\Api\Patrimony\LoanController::class, 'index']);
        Route::post('loans', [\App\Http\Controllers\Api\Patrimony\LoanController::class, 'store']);
        Route::post('loans/{loan}/return', [\App\Http\Controllers\Api\Patrimony\LoanController::class, 'returnLoan']);

        Route::get('spaces/bookings', [\App\Http\Controllers\Api\Patrimony\SpaceController::class, 'index']);
        Route::post('spaces/bookings', [\App\Http\Controllers\Api\Patrimony\SpaceController::class, 'store']);
        Route::post('spaces/bookings/{booking}/status', [\App\Http\Controllers\Api\Patrimony\SpaceController::class, 'updateStatus']);

        Route::apiResource('consumables', \App\Http\Controllers\Api\Patrimony\ConsumableController::class);
    });

    // Finance - Budgets & Obligations
    Route::prefix('finance')->group(function () {
        Route::get('budgets/{budget}/status', [\App\Http\Controllers\Api\BudgetController::class, 'status']);
        Route::get('budgets/{budget}/items', [\App\Http\Controllers\Api\BudgetController::class, 'items']);
        Route::post('budgets/{budget}/items', [\App\Http\Controllers\Api\BudgetController::class, 'storeItem']);
        Route::post('budgets/{budget}/movements', [\App\Http\Controllers\Api\BudgetController::class, 'storeMovement']);
        Route::apiResource('budgets', \App\Http\Controllers\Api\BudgetController::class);

        Route::get('remittances/preview', [\App\Http\Controllers\Api\RemittanceController::class, 'preview']);
        Route::post('remittances/generate', [\App\Http\Controllers\Api\RemittanceController::class, 'generate']);
        Route::apiResource('remittances', \App\Http\Controllers\Api\RemittanceController::class)->only(['index', 'show']);
    });
});
