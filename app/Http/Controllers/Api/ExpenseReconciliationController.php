<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReconciliation;
use App\Models\ExpenseReconciliationItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseReconciliationController extends Controller
{
    public function index()
    {
        $reconciliations = ExpenseReconciliation::with(['transaction', 'responsibleMember', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json($reconciliations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'responsible_member_id' => 'nullable|exists:members,id',
            'notes' => 'nullable|string',
        ]);

        $transaction = Transaction::findOrFail($validated['transaction_id']);
        
        // Prevent duplicate reconciliation for same transaction
        if ($transaction->reconciliation) {
            return response()->json(['message' => 'Esta transação já possui uma prestação de contas.'], 422);
        }

        $reconciliation = ExpenseReconciliation::create([
            'transaction_id' => $transaction->id,
            'responsible_member_id' => $validated['responsible_member_id'],
            'total_advanced' => $transaction->amount,
            'status' => 'open',
            'notes' => $validated['notes'],
        ]);

        return response()->json($reconciliation, 201);
    }

    public function show(ExpenseReconciliation $reconciliation)
    {
        $reconciliation->load(['transaction', 'responsibleMember', 'items.category', 'items.costCenter', 'closedBy']);
        return response()->json($reconciliation);
    }

    public function addItem(Request $request, ExpenseReconciliation $reconciliation)
    {
        if ($reconciliation->status === 'closed') {
            return response()->json(['message' => 'Esta prestação de contas já está fechada.'], 422);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'document_number' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120', // 5MB limit
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('reconciliations/attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        $item = $reconciliation->items()->create($validated);
        
        // Update total reconciled
        $reconciliation->update([
            'total_reconciled' => $reconciliation->items()->sum('amount')
        ]);

        return response()->json($item->load(['category', 'costCenter']), 201);
    }

    public function removeItem(ExpenseReconciliation $reconciliation, ExpenseReconciliationItem $item)
    {
        if ($reconciliation->status === 'closed') {
            return response()->json(['message' => 'Esta prestação de contas já está fechada.'], 422);
        }

        if ($item->reconciliation_id !== $reconciliation->id) {
            return response()->json(['message' => 'Item não pertence a esta prestação.'], 403);
        }

        if ($item->attachment_path) {
            Storage::disk('public')->delete($item->attachment_path);
        }

        $item->delete();

        $reconciliation->update([
            'total_reconciled' => $reconciliation->items()->sum('amount')
        ]);

        return response()->noContent();
    }

    public function close(Request $request, ExpenseReconciliation $reconciliation)
    {
        $reconciliation->update([
            'status' => 'closed',
            'closed_by' => $request->user()->id,
        ]);

        return response()->json($reconciliation);
    }

    public function pdf(ExpenseReconciliation $reconciliation)
    {
        $reconciliation->load(['transaction', 'responsibleMember', 'items.category', 'items.costCenter', 'closedBy']);
        
        $church = \App\Models\ChurchConfig::first(); // Assuming church info is here

        $pdf = Pdf::loadView('reports.expense-reconciliation', [
            'reconciliation' => $reconciliation,
            'church' => $church
        ]);

        return $pdf->download("prestacao-de-contas-{$reconciliation->id}.pdf");
    }
}
