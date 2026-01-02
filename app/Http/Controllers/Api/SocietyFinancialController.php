<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SocietyFinancialMovement;
use App\Models\SocietyMember;
use App\Models\SocietyDuesPayment;
use Illuminate\Support\Facades\DB;

class SocietyFinancialController extends Controller
{
    public function index($societyId)
    {
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
        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'category' => 'nullable|string'
        ]);

        $validated['society_id'] = $societyId;

        $movement = SocietyFinancialMovement::create($validated);
        return response()->json($movement, 201);
    }

    public function getDuesGrid($societyId, Request $request)
    {
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
        $validated = $request->validate([
            'society_member_id' => 'required|exists:society_members,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date'
        ]);

        // Create movement automatically
        DB::transaction(function() use ($validated, $societyId) {
            $payment = SocietyDuesPayment::create($validated);
            
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
}
