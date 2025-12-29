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
        $query = Transaction::where('status', 'confirmed');

        if ($request->has('startDate')) {
            $query->where('date', '>=', $request->startDate);
        }

        if ($request->has('endDate')) {
            $query->where('date', '<=', $request->endDate);
        }

        $transactions = $query->with(['member', 'category', 'costCenter'])
            ->orderBy('date', 'desc')
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        return response()->json([
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }
}
