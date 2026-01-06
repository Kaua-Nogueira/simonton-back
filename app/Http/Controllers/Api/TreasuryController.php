<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TreasuryController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', TreasuryEntry::class); // TODO: Policy
        
        $query = \App\Models\TreasuryEntry::with(['user', 'confirmer'])
            ->orderBy('date', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        // $this->authorize('create', TreasuryEntry::class);
        
        $validated = $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $entry = \App\Models\TreasuryEntry::create([
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
            'user_id' => $request->user()->id,
            'total_amount' => 0
        ]);

        return response()->json($entry, 201);
    }

    public function show($id)
    {
        $entry = \App\Models\TreasuryEntry::with(['cash', 'splits.member', 'user', 'confirmer'])->findOrFail($id);
        // $this->authorize('view', $entry);
        return response()->json($entry);
    }

    public function updateCash(Request $request, $id)
    {
        $entry = \App\Models\TreasuryEntry::findOrFail($id);
        // $this->authorize('update', $entry);

        if ($entry->status !== 'draft') {
            return response()->json(['message' => 'Cannot edit non-draft entries'], 403);
        }

        $validated = $request->validate([
            'cash' => 'required|array',
            'cash.*.denomination' => 'required|numeric',
            'cash.*.quantity' => 'required|integer|min:0',
        ]);

        // Sync Cash
        $entry->cash()->delete();
        $totalCash = 0;

        foreach ($validated['cash'] as $item) {
            $amount = $item['denomination'] * $item['quantity'];
            if ($item['quantity'] > 0) {
                 $entry->cash()->create([
                     'denomination' => $item['denomination'],
                     'quantity' => $item['quantity'],
                     'amount' => $amount
                 ]);
                 $totalCash += $amount;
            }
        }
        
        return response()->json(['message' => 'Cash updated', 'entry' => $entry->load('cash')]);
    }

    public function addSplit(Request $request, $id)
    {
        $entry = \App\Models\TreasuryEntry::findOrFail($id);
        // $this->authorize('update', $entry);

        if ($entry->status !== 'draft') {
            return response()->json(['message' => 'Cannot edits non-draft entries'], 403);
        }

        $validated = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:tithe,offering,mission,other',
            'is_digital' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $split = $entry->splits()->create($validated);
        
        return response()->json($split, 201);
    }

    public function removeSplit($id, $splitId)
    {
        $item = \App\Models\TreasurySplit::findOrFail($splitId);
        if ($item->entry_id != $id) abort(404);
        
        $entry = $item->entry;
        if ($entry->status !== 'draft') {
             return response()->json(['message' => 'Cannot edit non-draft entries'], 403);
        }

        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function submit(Request $request, $id)
    {
        $entry = \App\Models\TreasuryEntry::with(['splits'])->findOrFail($id);
        // $this->authorize('update', $entry);
        
        // Validation removed as per "Envelope-Only" workflow request.
        // We no longer perform a blind global count (Step 1), so there is nothing to compare against.
        // The sum of envelopes is considered the source of truth.
        
        $totalSplits = $entry->splits->sum('amount');
        
        $entry->update([
            'status' => 'pending',
            'total_amount' => $totalSplits
        ]);

        return response()->json($entry);
    }

    public function confirm(Request $request, $id)
    {
        $entry = \App\Models\TreasuryEntry::with(['cash', 'splits'])->findOrFail($id);
        // $this->authorize('confirm', $entry);

        if ($entry->status !== 'pending') {
            return response()->json(['message' => 'Entry must be pending'], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($entry, $request) {
            // 1. Mark as Confirmed
            $entry->update([
                'status' => 'confirmed',
                'confirmed_by' => $request->user()->id,
                'updated_at' => now()
            ]);

            // 2. Create Transaction in Finance Module
            foreach ($entry->splits as $split) {
                // Determine Category based on Type
                // STRICT MAPPING for Tithes to ensure they appear in member records
                $catName = match($split->type) {
                    'tithe' => 'Dízimos', // Must match exactly what Member Profile looks for
                    'offering' => 'Ofertas',
                    'mission' => 'Missões',
                    default => 'Outros',
                };
                
                // Find or Create Category
                $category = \App\Models\Category::firstOrCreate(
                    ['name' => $catName],
                    ['type' => 'income', 'description' => 'Categoria automática do sistema']
                );
                $categoryId = $category->id;
                
                // Cost Center: Default to "Geral"
                $costCenter = \App\Models\CostCenter::firstOrCreate(
                    ['name' => 'Geral'],
                    ['description' => 'Centro de custo padrão']
                );
                $costCenterId = $costCenter->id; 

                // Description
                $desc = "Diaconia #{$entry->id} - " . ($split->is_digital ? "Digital" : "Espécie");

                $transaction = \App\Models\Transaction::create([
                    'date' => $entry->date,
                    'description' => $desc,
                    'type' => 'income',
                    'amount' => $split->amount,
                    'category_id' => $categoryId,
                    'cost_center_id' => $costCenterId,
                    'member_id' => $split->member_id, // Important!
                    'status' => 'confirmed', // Already confirmed by Treasurer
                    'reconciled_by' => $request->user()->id,
                    'reconciled_at' => now(),
                    'user_id' => $request->user()->id, // Who created the system record
                    'notes' => $split->description
                ]);
            }
        });

        return response()->json(['message' => 'Confirmed and Processed', 'entry' => $entry]);
    }
}
