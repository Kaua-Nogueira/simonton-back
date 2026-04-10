<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SocietyFinancialMovement;
use App\Models\SocietyMember;
use App\Models\SocietyDuesPayment;
use App\Models\Society;
use Illuminate\Support\Facades\DB;

class SocietyFinancialController extends Controller
{
    public function index($societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('view', $society);

        $movements = SocietyFinancialMovement::where('society_id', $societyId)
            ->orderBy('date', 'desc')
            ->get();
            
        $balance = $movements->where('type', 'income')->sum('amount') - $movements->where('type', 'expense')->sum('amount');

        return response()->json([
            'movements' => $movements,
            'balance' => $balance
        ]);
    }

    public function storeMovement(Request $request, $societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('society_attachments', 'local');
            $validated['attachment_path'] = $path;
        }

        $validated['society_id'] = $societyId;

        $movement = SocietyFinancialMovement::create($validated);
        return response()->json($movement, 201);
    }

    public function confirmMovement($societyId, $movementId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $movement = SocietyFinancialMovement::where('society_id', $societyId)
            ->findOrFail($movementId);
            
        $movement->update(['is_confirmed' => true]);
        
        return response()->json($movement);
    }

    public function getDuesGrid($societyId, Request $request)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('view', $society);

        $year = $request->input('year', date('Y'));
        
        // Get all members even if they haven't paid
        $members = SocietyMember::where('society_id', $societyId)
            ->with(['member', 'duesPayments' => function($q) use ($year) {
                $q->where('year', $year);
            }])
            ->get();

        return response()->json([
            'year' => $year,
            'members' => $members
        ]);
    }

    public function payDues(Request $request, $societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $validated = $request->validate([
            'society_member_id' => 'required|exists:society_members,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date'
        ]);

        // Create movement automatically
        DB::transaction(function() use ($validated, $societyId) {
            SocietyDuesPayment::create($validated);
            
            SocietyFinancialMovement::create([
                'society_id' => $societyId,
                'description' => "Mensalidade " . $validated['month'] . "/" . $validated['year'],
                'amount' => $validated['amount'],
                'type' => 'income',
                'date' => $validated['payment_date'],
                'category' => 'Mensalidade'
            ]);
        });

        return response()->json(['message' => 'Pago e registrado no caixa.']);
    }

    public function batchPayDues(Request $request, $societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $validated = $request->validate([
            'members' => 'required|array',
            'members.*' => 'exists:society_members,id',
            'months' => 'required|array',
            'months.*' => 'integer|min:1|max:12',
            'year' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date'
        ]);

        DB::transaction(function() use ($validated, $societyId) {
            $totalAmount = 0;
            $processedCount = 0;
            $memberCount = count($validated['members']);
            $monthCount = count($validated['months']);

            foreach ($validated['members'] as $memberId) {
                foreach ($validated['months'] as $month) {
                    $exists = SocietyDuesPayment::where('society_member_id', $memberId)
                        ->where('year', $validated['year'])
                        ->where('month', $month)
                        ->exists();
                    
                    if (!$exists) {
                        SocietyDuesPayment::create([
                            'society_member_id' => $memberId,
                            'year' => $validated['year'],
                            'month' => $month,
                            'amount' => $validated['amount'],
                            'payment_date' => $validated['payment_date']
                        ]);

                        $totalAmount += $validated['amount'];
                        $processedCount++;
                    }
                }
            }

            if ($processedCount > 0) {
                // Determine description
                $description = "Recebimento Lote Mensalidades ({$processedCount} lançamentos)";
                if ($monthCount === 1) {
                    $description = "Mensalidades em Lote - " . $validated['months'][0] . "/" . $validated['year'] . " ({$memberCount} sócios)";
                }

                SocietyFinancialMovement::create([
                    'society_id' => $societyId,
                    'description' => $description,
                    'amount' => $totalAmount,
                    'type' => 'income',
                    'date' => $validated['payment_date'],
                    'category' => 'Mensalidade'
                ]);
            }
        });

        return response()->json(['message' => 'Pagamentos em lote registrados com sucesso.']);
    }
}
