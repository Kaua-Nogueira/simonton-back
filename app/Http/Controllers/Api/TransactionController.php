<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTransactionRequest;
use App\Http\Requests\SplitTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\AuditService;
use App\Services\AutoSuggestionService;
use App\Services\CashBalanceService;
use App\Services\OFXImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(
        private AutoSuggestionService $suggestionService,
        private CashBalanceService $balanceService,
        private AuditService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $query = Transaction::with(['member', 'category', 'costCenter', 'reconciledBy', 'society', 'reconciliation'])
            ->whereNull('parent_transaction_id');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $transactions = $query->orderBy('id', 'desc')->get();

        return response()->json(TransactionResource::collection($transactions));
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);
        $transaction->load(['member', 'category', 'costCenter', 'splitTransactions.category', 'splitTransactions.costCenter', 'reconciledBy']);
        
        return response()->json(new TransactionResource($transaction));
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', Transaction::class);
        try {
            $data = $request->validated();

            // Force pending status if category is missing (cost center is now optional)
            if (empty($data['category_id'])) {
                $data['status'] = 'pending';
            }

            $transaction = Transaction::create($data);

            if ($transaction->status === 'pending') {
                $this->handleSuggestion($transaction);
            }

            $this->auditService->log($transaction, 'created');

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => new TransactionResource($transaction->fresh()),
            ], 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Store Transaction Error', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Não foi possível criar a transação no momento.',
            ], 500);
        }
    }

    private function handleSuggestion(Transaction $transaction): void
    {
        $suggestions = $this->suggestionService->suggestMemberAndCategory($transaction);
        
        if ($suggestions['confidence'] > 0) {
            $updateData = [];

            // Only suggest if the field is currently null (preserve manual user input)
            if (!$transaction->member_id && $suggestions['member_id']) {
                $updateData['member_id'] = $suggestions['member_id'];
            }
            if (!$transaction->category_id && $suggestions['category_id']) {
                $updateData['category_id'] = $suggestions['category_id'];
            }
            if (!$transaction->cost_center_id && $suggestions['cost_center_id']) {
                $updateData['cost_center_id'] = $suggestions['cost_center_id'];
            }

            if (!empty($updateData)) {
                $updateData['suggestion_confidence'] = $suggestions['confidence'];
                $updateData['status'] = 'suggested';

                // AUTO-CONFIRM if confidence is extremely high (User request: don't require post-info)
                // RULE: Never auto-confirm member expenses (needs reconciliation first)
                if ($suggestions['confidence'] >= 95) {
                    $isMemberExpense = $transaction->type === 'expense' && ($transaction->member_id || !empty($updateData['member_id']));
                    
                    if (!$isMemberExpense) {
                        $updateData['status'] = 'confirmed';
                        $updateData['reconciled_by'] = auth()->id() ?? 1; // System or Current User
                        $updateData['reconciled_at'] = now();
                    }
                }

                $transaction->update($updateData);
            }

            if ($transaction->status === 'confirmed') {
                $this->balanceService->recalculateFromDate($transaction->date);
                $this->auditService->logReconciliation($transaction, [
                    'old' => ['status' => 'pending'],
                    'new' => $transaction->only(['member_id', 'category_id', 'cost_center_id', 'status']),
                    'auto' => true
                ]);
            }
        }
    }

    public function confirm(ConfirmTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('confirm', $transaction);

        if ($transaction->date->isFuture()) {
            return response()->json([
                'message' => 'A data da transação não pode estar no futuro.',
            ], 422);
        }

        // RULE: Member expenses MUST be reconciled ("batidas") before hitting the cash balance (confirmation)
        $isMemberExpense = $transaction->type === 'expense' && ($request->memberId || $transaction->member_id);
        if ($isMemberExpense && !$transaction->isReconciliationClosed()) {
            return response()->json([
                'message' => 'Esta saída está vinculada a um membro e exige que a prestação de contas seja finalizada ("batida") antes da confirmação bancária.',
            ], 422);
        }

        DB::transaction(function () use ($request, $transaction) {
            $oldValues = $transaction->only(['member_id', 'category_id', 'cost_center_id', 'status']);

            $transaction->update([
                'member_id' => $request->memberId,
                'category_id' => $request->categoryId,
                'cost_center_id' => $request->costCenterId,
                'status' => 'confirmed',
                'reconciled_by' => $request->user()?->id,
                'reconciled_at' => now(),
            ]);

            $this->balanceService->recalculateFromDate($transaction->date);

            $this->auditService->logReconciliation($transaction, [
                'old' => $oldValues,
                'new' => $transaction->only(['member_id', 'category_id', 'cost_center_id', 'status']),
            ]);
        });

        return response()->json([
            'message' => 'Transaction confirmed successfully',
            'data' => new TransactionResource($transaction->fresh()),
        ]);
    }

    public function split(SplitTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('split', $transaction);

        $splits = $request->splits;
        
        $totalSplitAmount = collect($splits)->sum('amount');
        if (abs($totalSplitAmount - $transaction->amount) > 0.01) {
            return response()->json([
                'message' => 'A soma das divisões deve ser exatamente igual ao valor total da transação',
                'expected' => (float) $transaction->amount,
                'received' => (float) $totalSplitAmount,
                'difference' => (float) abs($totalSplitAmount - $transaction->amount),
            ], 422);
        }

        DB::transaction(function () use ($request, $splits, $transaction) {
            // Create split transactions
            foreach ($splits as $split) {
                $transaction->splitTransactions()->create([
                    'amount' => $split['amount'],
                    'category_id' => $split['categoryId'],
                    'cost_center_id' => $split['costCenterId'] ?? null,
                    'member_id' => $split['memberId'] ?? null,
                    'notes' => $split['notes'] ?? null,
                ]);
            }

            // Update parent transaction status
            $transaction->update([
                'status' => 'split',
                'reconciled_by' => $request->user()?->id,
                'reconciled_at' => now(),
            ]);

            $this->auditService->logSplit($transaction, $splits);
            
            $this->balanceService->recalculateFromDate($transaction->date);
        });

        return response()->json([
            'message' => 'Transaction split successfully',
            'data' => new TransactionResource($transaction->fresh(['splitTransactions'])),
        ]);
    }

    public function importOFX(Request $request): JsonResponse
    {
        $this->authorize('import', Transaction::class);

        $request->validate([
            'file' => 'required|file|mimes:ofx',
        ]);

        $transactions = [];
        $duplicates = 0;

        DB::transaction(function () use ($request, &$transactions, &$duplicates) {
            $file = $request->file('file');
            $service = new OFXImportService();
            $importedTransactions = $service->import($file);

            foreach ($importedTransactions as $importedTransaction) {
                $exists = Transaction::where('date', $importedTransaction['date'])
                    ->where('amount', $importedTransaction['amount'])
                    ->where('description', $importedTransaction['description'])
                    ->exists();

                if ($exists) {
                    $duplicates++;
                    continue;
                }

                $transaction = Transaction::create($importedTransaction);

                $this->handleSuggestion($transaction);

                $this->auditService->log($transaction, 'imported');
                $transactions[] = $transaction;
            }
        });

        return response()->json([
            'message' => 'OFX file imported successfully',
            'imported' => count($transactions),
            'duplicates' => $duplicates,
            'data' => TransactionResource::collection($transactions),
        ]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        DB::transaction(function () use ($transaction) {
            $date = $transaction->date;
            
            $this->auditService->log($transaction, 'deleted');
            
            $transaction->delete();
            
            $this->balanceService->recalculateFromDate($date);
        });

        return response()->json([
            'message' => 'Lançamento excluído com sucesso.',
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        // Assuming pending listing uses same perm as index or specific?
        // Let's use viewAny/index for safety
        $this->authorize('viewAny', Transaction::class);

        $transactions = Transaction::with(['member', 'category', 'costCenter', 'reconciliation'])
            ->whereIn('status', ['pending', 'suggested'])
            ->whereNull('parent_transaction_id')
            ->orderBy('suggestion_confidence', 'desc')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(TransactionResource::collection($transactions));
    }
}
