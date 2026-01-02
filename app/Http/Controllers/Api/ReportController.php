<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function show(string $type, Request $request): JsonResponse
    {
        $startDate = $request->input('startDate', now()->startOfMonth());
        $endDate = $request->input('endDate', now()->endOfMonth());

        return match ($type) {
            'income' => $this->incomeReport($startDate, $endDate),
            'expense' => $this->expenseReport($startDate, $endDate),
            'category' => $this->categoryReport($startDate, $endDate),
            'member' => $this->memberReport($startDate, $endDate),
            'transfer' => $this->transferReport($startDate, $endDate),
            default => response()->json(['message' => 'Invalid report type'], 400),
        };
    }

    private function incomeReport($startDate, $endDate): JsonResponse
    {
        $data = Transaction::where('type', 'income')
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['member', 'category', 'costCenter'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'type' => 'income',
            'total' => $data->sum('amount'),
            'count' => $data->count(),
            'transactions' => $data,
        ]);
    }

    private function expenseReport($startDate, $endDate): JsonResponse
    {
        $data = Transaction::where('type', 'expense')
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['member', 'category', 'costCenter'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'type' => 'expense',
            'total' => $data->sum('amount'),
            'count' => $data->count(),
            'transactions' => $data,
        ]);
    }

    private function categoryReport($startDate, $endDate): JsonResponse
    {
        $data = Transaction::select('category_id', 'type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('category_id', 'type')
            ->with('category')
            ->get();

        return response()->json([
            'type' => 'category',
            'data' => $data,
        ]);
    }

    private function memberReport($startDate, $endDate): JsonResponse
    {
        $data = Transaction::select('member_id', 'type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'confirmed')
            ->whereNotNull('member_id')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('member_id', 'type')
            ->with('member')
            ->get();

        return response()->json([
            'type' => 'member',
            'data' => $data,
        ]);
    }

    private function transferReport($startDate, $endDate): JsonResponse
    {
        // Fetch members transferred/dismissed within the range
        // dismissal_type = 'Transferência'
        $data = \App\Models\Member::where('dismissal_type', 'Transferência')
            ->whereBetween('dismissal_date', [$startDate, $endDate])
            ->orderBy('dismissal_date', 'desc')
            ->get();

        return response()->json([
            'type' => 'transfer',
            'count' => $data->count(),
            'data' => $data,
        ]);
    }
}
