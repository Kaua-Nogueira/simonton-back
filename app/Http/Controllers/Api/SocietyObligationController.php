<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocietyObligation;
use App\Models\SocietyFinancialMovement;
use App\Models\Society;
use Illuminate\Support\Facades\DB;

class SocietyObligationController extends Controller
{
    public function index($societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('view', $society);

        $obligations = SocietyObligation::where('society_id', $societyId)
            ->orderBy('due_date', 'asc')
            ->get();
            
        return response()->json($obligations);
    }

    public function store(Request $request, $societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $validated = $request->validate([
            'description' => 'required|string',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,overdue'
        ]);

        $validated['society_id'] = $societyId;

        $obligation = SocietyObligation::create($validated);
        return response()->json($obligation, 201);
    }

    public function pay(Request $request, $societyId, $obligationId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $obligation = SocietyObligation::where('society_id', $societyId)
            ->findOrFail($obligationId);
            
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        return DB::transaction(function() use ($obligation, $validated, $request, $societyId) {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('society_attachments', 'local');
            }

            // Create movement
            $movement = SocietyFinancialMovement::create([
                'society_id' => $societyId,
                'description' => "Pagamento Obrigação: " . $obligation->description,
                'amount' => $obligation->amount,
                'type' => 'expense',
                'date' => $validated['payment_date'],
                'category' => 'Repasse',
                'attachment_path' => $attachmentPath,
                'is_confirmed' => true
            ]);

            $obligation->update([
                'status' => 'paid',
                'movement_id' => $movement->id
            ]);

            return response()->json([
                'message' => 'Obrigação paga e registrada no caixa.',
                'obligation' => $obligation->load('movement')
            ]);
        });
    }
}
