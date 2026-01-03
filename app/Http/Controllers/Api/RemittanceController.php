<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remittance;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemittanceController extends Controller
{
    public function index()
    {
        return Remittance::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    public function preview(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $baseAmount = Transaction::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('type', 'income')
            ->whereHas('category', function ($q) {
                $q->where('is_taxable', true);
            })
            ->sum('amount');

        return response()->json([
            'year' => $year,
            'month' => $month,
            'base_amount' => $baseAmount,
            'remittance_amount' => $baseAmount * 0.10
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        return DB::transaction(function () use ($validated) {
            $existing = Remittance::where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Remittance already generated for this period'], 422);
            }

            $baseAmount = Transaction::whereYear('date', $validated['year'])
                ->whereMonth('date', $validated['month'])
                ->where('type', 'income')
                ->whereHas('category', function ($q) {
                    $q->where('is_taxable', true);
                })
                ->sum('amount');

            $amount = $baseAmount * 0.10;

            // Create Liability Transaction (Account Payable)
            // Assuming we have a category for "Remessa Conciliar" or we create a generic one?
            // For now, let's look for a category named "Remessas" or create one if needed?
            // BETTER: Let the user handle the classification or auto-assign if possible.
            // Simplified: Just create the Remittance record. The Payment transaction will be linked later manually or implementation of "Pay" button.
            
            // ACTUALLY: The requirement says "Cria uma 'Conta a Pagar' automática".
            // So we should create a suggested transaction.

            $remittanceCategory = Category::firstOrCreate(
                ['name' => 'Remessas Conciliares', 'type' => 'expense'],
                ['description' => 'Repasses para órgãos superiores (10%)']
            );

            $transaction = Transaction::create([
                'type' => 'expense',
                'amount' => $amount,
                'description' => "Remessa Conciliar Ref. {$validated['month']}/{$validated['year']}",
                'date' => now()->setDate($validated['year'], $validated['month'], 28), // Due date approx
                'category_id' => $remittanceCategory->id,
                'status' => 'pending', // Account Payable
            ]);

            $remittance = Remittance::create([
                'year' => $validated['year'],
                'month' => $validated['month'],
                'base_amount' => $baseAmount,
                'amount' => $amount,
                'status' => 'pending',
                'transaction_id' => $transaction->id
            ]);

            return $remittance;
        });
    }
}
