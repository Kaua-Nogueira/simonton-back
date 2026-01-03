<?php

namespace App\Http\Controllers\Api\Patrimony;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetLoan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetLoan::with(['asset', 'member']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('checkout_date')->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'requester_name' => 'required|string',
            'member_id' => 'nullable|exists:members,id',
            'checkout_date' => 'required|date',
            'expected_return_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Check if asset is available
        // Could enable this check or allow override
        // $activeLoan = AssetLoan::where('asset_id', $validated['asset_id'])
        //     ->where('status', 'active')
        //     ->exists();
        // if ($activeLoan) return response()->json(['message' => 'Asset already on loan'], 422);

        $loan = AssetLoan::create($validated);
        return response()->json($loan, 201);
    }

    public function returnLoan(Request $request, AssetLoan $loan)
    {
        $validated = $request->validate([
            'actual_return_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $loan->update([
            'status' => 'returned',
            'actual_return_date' => $validated['actual_return_date'],
            'notes' => ($loan->notes ? $loan->notes . "\n" : "") . ($validated['notes'] ?? ''),
        ]);

        return response()->json($loan);
    }
}
