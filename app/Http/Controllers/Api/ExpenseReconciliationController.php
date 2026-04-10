<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReconciliation;
use App\Models\ExpenseReconciliationItem;
use App\Models\Society;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ExpenseReconciliation::class);

        $allowedSocietyIds = $this->allowedSocietyIds($request);

        $reconciliations = ExpenseReconciliation::with(['transaction', 'responsibleMember', 'items'])
            ->whereHas('transaction', function ($query) use ($allowedSocietyIds) {
                $query->whereNull('society_id');
                if (!empty($allowedSocietyIds)) {
                    $query->orWhereIn('society_id', $allowedSocietyIds);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reconciliations);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ExpenseReconciliation::class);

        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'responsible_member_id' => 'nullable|exists:members,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $transaction = Transaction::findOrFail($validated['transaction_id']);
        $this->ensureTransactionScope($request, $transaction);

        if ($transaction->reconciliation) {
            return response()->json(['message' => 'Esta transacao ja possui uma prestacao de contas.'], 422);
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

    public function show(Request $request, ExpenseReconciliation $reconciliation)
    {
        $this->authorize('view', $reconciliation);
        $this->ensureReconciliationScope($request, $reconciliation);

        $reconciliation->load(['transaction', 'responsibleMember', 'items.category', 'items.costCenter', 'closedBy']);
        return response()->json($reconciliation);
    }

    public function addItem(Request $request, ExpenseReconciliation $reconciliation)
    {
        $this->authorize('update', $reconciliation);
        $this->ensureReconciliationScope($request, $reconciliation);

        if ($reconciliation->status === 'closed') {
            return response()->json(['message' => 'Esta prestacao de contas ja esta fechada.'], 422);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'document_number' => 'nullable|string|max:100',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('reconciliations/attachments', 'local');
            $validated['attachment_path'] = $path;
        }

        $item = $reconciliation->items()->create($validated);

        $reconciliation->update([
            'total_reconciled' => $reconciliation->items()->sum('amount'),
        ]);

        return response()->json($item->load(['category', 'costCenter']), 201);
    }

    public function removeItem(Request $request, ExpenseReconciliation $reconciliation, ExpenseReconciliationItem $item)
    {
        $this->authorize('update', $reconciliation);
        $this->ensureReconciliationScope($request, $reconciliation);

        if ($reconciliation->status === 'closed') {
            return response()->json(['message' => 'Esta prestacao de contas ja esta fechada.'], 422);
        }

        if ($item->reconciliation_id !== $reconciliation->id) {
            return response()->json(['message' => 'Item nao pertence a esta prestacao.'], 403);
        }

        if ($item->attachment_path) {
            Storage::disk('local')->delete($item->attachment_path);
        }

        $item->delete();

        $reconciliation->update([
            'total_reconciled' => $reconciliation->items()->sum('amount'),
        ]);

        return response()->noContent();
    }

    public function close(Request $request, ExpenseReconciliation $reconciliation)
    {
        $this->authorize('update', $reconciliation);
        $this->ensureReconciliationScope($request, $reconciliation);

        $reconciliation->update([
            'status' => 'closed',
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
        ]);

        return response()->json($reconciliation);
    }

    public function pdf(Request $request, ExpenseReconciliation $reconciliation)
    {
        $this->authorize('view', $reconciliation);
        $this->ensureReconciliationScope($request, $reconciliation);

        $reconciliation->load(['transaction', 'responsibleMember', 'items.category', 'items.costCenter', 'closedBy']);
        $church = \App\Models\ChurchConfig::first();

        $pdf = Pdf::loadView('reports.expense-reconciliation', [
            'reconciliation' => $reconciliation,
            'church' => $church,
        ]);

        return $pdf->download("prestacao-de-contas-{$reconciliation->id}.pdf");
    }

    private function ensureReconciliationScope(Request $request, ExpenseReconciliation $reconciliation): void
    {
        $transaction = $reconciliation->transaction;
        if (!$transaction) {
            abort(403, 'Prestacao sem transacao valida.');
        }

        $this->ensureTransactionScope($request, $transaction);
    }

    private function ensureTransactionScope(Request $request, Transaction $transaction): void
    {
        if (!$transaction->society_id) {
            return;
        }

        $allowedSocietyIds = $this->allowedSocietyIds($request);
        if (!in_array((int) $transaction->society_id, $allowedSocietyIds, true)) {
            abort(403, 'Transacao fora do escopo autorizado.');
        }
    }

    private function allowedSocietyIds(Request $request): array
    {
        $user = $request->user();
        if (!$user || $user->isSuperAdmin()) {
            return Society::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return Society::query()
            ->get()
            ->filter(fn (Society $society) => $user->can('view', $society))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
