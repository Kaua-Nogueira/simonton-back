<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CostCenterController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

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

// Categories
Route::get('/categories', [CategoryController::class, 'index']);

// Cost Centers
Route::get('/cost-centers', [CostCenterController::class, 'index']);

// Cash Register
Route::get('/cash-register', [CashRegisterController::class, 'index']);
Route::get('/cash-register/balance', [CashRegisterController::class, 'currentBalance']);

// Dashboard
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

// Reports
Route::get('/reports/{type}', [ReportController::class, 'show']);
