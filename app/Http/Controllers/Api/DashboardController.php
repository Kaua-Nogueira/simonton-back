<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Current month stats
        $currentIncome = Transaction::where('type', 'income')
            ->where('status', 'confirmed')
            ->whereDate('date', '>=', $currentMonth)
            ->sum('amount');

        $currentExpense = Transaction::where('type', 'expense')
            ->where('status', 'confirmed')
            ->whereDate('date', '>=', $currentMonth)
            ->sum('amount');

        // Last month stats
        $lastIncome = Transaction::where('type', 'income')
            ->where('status', 'confirmed')
            ->whereDate('date', '>=', $lastMonth)
            ->whereDate('date', '<', $currentMonth)
            ->sum('amount');

        $lastExpense = Transaction::where('type', 'expense')
            ->where('status', 'confirmed')
            ->whereDate('date', '>=', $lastMonth)
            ->whereDate('date', '<', $currentMonth)
            ->sum('amount');

        // Pending transactions
        $pendingCount = Transaction::where('status', 'pending')->count();

        // Top categories
        $topCategories = Transaction::select('category_id', DB::raw('SUM(amount) as total'))
            ->where('status', 'confirmed')
            ->whereDate('date', '>=', $currentMonth)
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('category')
            ->get();

        return response()->json([
            'currentMonth' => [
                'income' => $currentIncome,
                'expense' => $currentExpense,
                'balance' => $currentIncome - $currentExpense,
            ],
            'lastMonth' => [
                'income' => $lastIncome,
                'expense' => $lastExpense,
                'balance' => $lastIncome - $lastExpense,
            ],
            'pendingTransactions' => $pendingCount,
            'topCategories' => $topCategories,
            'totalMembers' => Member::where('status', 'active')->count(),
            'societiesStats' => \App\Models\Society::withCount('members')->get()->map(function($society) {
                return [
                    'id' => $society->id,
                    'name' => $society->name,
                    'count' => $society->members_count,
                    'abbreviation' => $society->abbreviation
                ];
            }),
        ]);
    }
}
