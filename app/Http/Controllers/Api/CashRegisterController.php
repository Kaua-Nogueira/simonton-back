<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::whereIn('status', ['confirmed', 'split']);

        if ($request->has('startDate')) {
            $query->where('date', '>=', $request->startDate);
        }

        if ($request->has('endDate')) {
            $query->where('date', '<=', $request->endDate);
        }

        // Get transactions ordered by date descending
        $transactions = $query->with(['member', 'category', 'costCenter'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Calculate totals
        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        // Calculate running balance
        // First, get the absolute total balance of the system up to now (or relative to filters?)
        // For a true cash register, correct order verification usually relies on a known starting point.
        // Simplified approach: Calculate backwards from the current total balance? 
        // Or assume the list is the source of truth if pagination isn't involved.
        
        // Better approach for filtering: 
        // 1. Get Balance BEFORE the start date (opening balance).
        // 2. Iterate forwards.
        
        // HOWEVER, to keep it simple and consistent with the user's request for "showing entries",
        // let's calculate the "Balance After" for each transaction assuming the current set is complete 
        // OR calculate correctly by fetching the total balance first.

        $totalIncomeAllTime = Transaction::whereIn('status', ['confirmed', 'split'])->where('type', 'income')->sum('amount');
        $totalExpenseAllTime = Transaction::whereIn('status', ['confirmed', 'split'])->where('type', 'expense')->sum('amount');
        $currentSystemBalance = $totalIncomeAllTime - $totalExpenseAllTime;

        // If we have date filters, the "current" balance of the LATEST displayed transaction might NOT be the system balance
        // if the filter excludes future transactions. 
        // But usually Cash Register shows past. 
        
        // Let's iterate backwards from the newest transaction having the current system balance (if no future filter).
        // If filter is active, we need the balance at the end of the filtered period.
        
        // Robust way:
        // Calculate balance at end of query period.
        $endDate = $request->input('endDate', now());
        $incomeUntilEnd = Transaction::whereIn('status', ['confirmed', 'split'])
            ->where('date', '<=', $endDate)
            ->where('type', 'income')
            ->sum('amount');
        $expenseUntilEnd = Transaction::whereIn('status', ['confirmed', 'split'])
            ->where('date', '<=', $endDate)
            ->where('type', 'expense')
            ->sum('amount');
            
        $runningBalance = $incomeUntilEnd - $expenseUntilEnd;

        // Clone to avoid modifying the original collection while iterating
        $processedTransactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $transaction->balance_after = $runningBalance;
            
            // Revert the operation to find balance before
            if ($transaction->type === 'income') {
                $runningBalance -= $transaction->amount;
            } else {
                $runningBalance += $transaction->amount;
            }
            
            $transaction->balance_before = $runningBalance;
            return $transaction;
        });

        return response()->json([
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance, // Period balance
            'current_balance' => $currentSystemBalance, // Total system balance
            'transactions' => $processedTransactions,
        ]);
    }
}
