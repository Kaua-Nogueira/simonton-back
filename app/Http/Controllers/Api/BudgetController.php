<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        return Budget::withCount('items')->orderBy('year', 'desc')->get();
    }

    public function show(Budget $budget)
    {
        return $budget->load('items.category');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|unique:budgets,year',
            'description' => 'required|string',
        ]);

        return Budget::create($validated);
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'description' => 'string',
            'is_active' => 'boolean',
        ]);

        $budget->update($validated);
        return $budget;
    }

    // Budget Items (Targets)
    public function items(Budget $budget)
    {
        return $budget->items()->with('category')->get();
    }

    public function storeItem(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'initial_amount' => 'required|numeric|min:0',
        ]);

        $item = $budget->items()->updateOrCreate(
            ['category_id' => $validated['category_id']],
            [
                'initial_amount' => $validated['initial_amount'],
                'current_amount' => $validated['initial_amount'] // Initially same
            ]
        );

        return $item;
    }

    // Transpositions and Supplementations
    public function storeMovement(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'source_item_id' => 'nullable|exists:budget_items,id',
            'target_item_id' => 'nullable|exists:budget_items,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        if (!$validated['source_item_id'] && !$validated['target_item_id']) {
            return response()->json(['message' => 'Source or Target must be provided'], 422);
        }

        return DB::transaction(function () use ($validated, $budget) {
            // Check Source Balance
            if ($validated['source_item_id']) {
                $source = BudgetItem::lockForUpdate()->find($validated['source_item_id']);
                if ($source->current_amount < $validated['amount']) {
                    abort(422, 'Insufficient funds in source category');
                }
                $source->decrement('current_amount', $validated['amount']);
            }

            // Add to Target
            if ($validated['target_item_id']) {
                $target = BudgetItem::lockForUpdate()->find($validated['target_item_id']);
                $target->increment('current_amount', $validated['amount']);
            }

            $movement = $budget->movements()->create([
                'source_item_id' => $validated['source_item_id'] ?? null,
                'target_item_id' => $validated['target_item_id'] ?? null,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            return $movement;
        });
    }

    // Dashboard Data: Budget vs Actual
    public function status(Budget $budget)
    {
        // Calculate spent per category for the budget year
        $spending = DB::table('transactions')
            ->select('category_id', DB::raw('SUM(amount) as spent'))
            ->whereYear('date', $budget->year)
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $items = $budget->items()->with('category')->get()->map(function ($item) use ($spending) {
            $spent = $spending->get($item->category_id)?->spent ?? 0;
            return [
                'category' => $item->category->name,
                'budgeted' => $item->current_amount,
                'spent' => $spent,
                'remaining' => $item->current_amount - $spent,
                'percent' => $item->current_amount > 0 ? ($spent / $item->current_amount) * 100 : 0
            ];
        });

        return $items;
    }
}
