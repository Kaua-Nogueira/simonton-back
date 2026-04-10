<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Report;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function show(string $type, Request $request): JsonResponse
    {
        $this->authorize('view', Report::class);

        $validated = $request->validate([
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $startDate = $validated['startDate'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['endDate'] ?? now()->endOfMonth()->toDateString();
        $perPage = (int) ($validated['per_page'] ?? 50);

        return match ($type) {
            'income' => $this->incomeReport($startDate, $endDate, $perPage),
            'expense' => $this->expenseReport($startDate, $endDate, $perPage),
            'category' => $this->categoryReport($startDate, $endDate),
            'member' => $this->memberReport($startDate, $endDate, $perPage),
            'transfer' => $this->transferReport($startDate, $endDate, $perPage),
            'trend' => $this->trendReport(),
            default => response()->json(['message' => 'Invalid report type'], 400),
        };
    }

    private function trendReport(): JsonResponse
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $income = Transaction::where('type', 'income')
                ->where('status', 'confirmed')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $expense = Transaction::where('type', 'expense')
                ->where('status', 'confirmed')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $data[] = [
                'month' => $month->format('M'),
                'entradas' => $income,
                'saidas' => $expense,
                'balance' => $income - $expense,
            ];
        }

        return response()->json([
            'type' => 'trend',
            'data' => $data,
        ]);
    }

    private function incomeReport(string $startDate, string $endDate, int $perPage): JsonResponse
    {
        $query = Transaction::where('type', 'income')
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['member:id,name', 'category:id,name', 'costCenter:id,name'])
            ->orderBy('date', 'desc');

        $total = (clone $query)->sum('amount');
        $count = (clone $query)->count();
        $transactions = $query->paginate($perPage);

        return response()->json([
            'type' => 'income',
            'total' => $total,
            'count' => $count,
            'transactions' => $transactions,
        ]);
    }

    private function expenseReport(string $startDate, string $endDate, int $perPage): JsonResponse
    {
        $query = Transaction::where('type', 'expense')
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['member:id,name', 'category:id,name', 'costCenter:id,name'])
            ->orderBy('date', 'desc');

        $total = (clone $query)->sum('amount');
        $count = (clone $query)->count();
        $transactions = $query->paginate($perPage);

        return response()->json([
            'type' => 'expense',
            'total' => $total,
            'count' => $count,
            'transactions' => $transactions,
        ]);
    }

    private function categoryReport(string $startDate, string $endDate): JsonResponse
    {
        $data = Transaction::select('category_id', 'type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('category_id', 'type')
            ->with('category:id,name')
            ->get();

        return response()->json([
            'type' => 'category',
            'data' => $data,
        ]);
    }

    private function memberReport(string $startDate, string $endDate, int $perPage): JsonResponse
    {
        $data = Transaction::select('member_id', 'type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'confirmed')
            ->whereNotNull('member_id')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('member_id', 'type')
            ->with('member:id,name')
            ->paginate($perPage);

        return response()->json([
            'type' => 'member',
            'data' => $data,
        ]);
    }

    private function transferReport(string $startDate, string $endDate, int $perPage): JsonResponse
    {
        $query = Member::select(['id', 'name', 'dismissal_date', 'destination_church'])
            ->whereIn('dismissal_type', ['Transferencia', 'Transferência'])
            ->whereBetween('dismissal_date', [$startDate, $endDate])
            ->orderBy('dismissal_date', 'desc');

        $count = (clone $query)->count();

        return response()->json([
            'type' => 'transfer',
            'count' => $count,
            'data' => $query->paginate($perPage),
        ]);
    }
}
