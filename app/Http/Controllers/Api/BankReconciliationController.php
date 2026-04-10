<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\Category;
use App\Models\FinancialAccess;
use App\Models\Member;
use App\Models\Society;
use App\Models\Transaction;
use App\Services\OFXImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankReconciliationController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $request->validate([
            'file' => 'required|file|mimes:ofx,txt|max:10240',
            'bank_name' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $service = new OFXImportService();
        $importedData = $service->import($file);

        $statement = DB::transaction(function () use ($file, $request, $importedData) {
            $statement = BankStatement::create([
                'filename' => $file->getClientOriginalName(),
                'bank_name' => $request->bank_name ?? 'Imported Statement',
                'user_id' => Auth::id(),
                'total_entries' => count($importedData),
                'start_date' => collect($importedData)->min('date'),
                'end_date' => collect($importedData)->max('date'),
            ]);

            foreach ($importedData as $data) {
                $ref = $data['fit_id'] ?? null;

                if ($ref && BankTransaction::where('bank_ref', $ref)->exists()) {
                    continue;
                }

                BankTransaction::create([
                    'bank_statement_id' => $statement->id,
                    'date' => $data['date'],
                    'amount' => $data['amount'],
                    'description' => $data['description'],
                    'bank_ref' => $ref,
                    'status' => 'pending',
                ]);
            }

            return $statement;
        });

        return response()->json([
            'message' => 'Extrato importado com sucesso',
            'statement' => $statement->load('transactions'),
        ], 201);
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $allowedSocietyIds = $this->allowedSocietyIds($request);

        $bankTransactions = BankTransaction::where('status', 'pending')
            ->orderBy('date', 'desc')
            ->get();

        $manualTransactions = Transaction::whereIn('status', ['confirmed', 'pending', 'suggested'])
            ->whereNull('bank_transaction_id')
            ->where(function ($q) {
                $q->whereDoesntHave('society', function ($sub) {
                    $sub->where('name', 'LIKE', '%Diaconia%')
                        ->orWhere('name', 'LIKE', '%Tesouraria%');
                })->orWhereNull('society_id');
            })
            ->where(function ($q) use ($allowedSocietyIds) {
                $q->whereNull('society_id');
                if (!empty($allowedSocietyIds)) {
                    $q->orWhereIn('society_id', $allowedSocietyIds);
                }
            })
            ->with(['category', 'member', 'society'])
            ->orderBy('date', 'desc')
            ->get();

        $allMembers = Member::whereNotNull('cpf')->get(['id', 'name', 'cpf']);

        $bankTransactions = $bankTransactions->map(function ($bank) use ($manualTransactions, $allMembers) {
            $bankAmount = (float) $bank->amount;
            $bankDescNormalized = Str::ascii(strtolower((string) $bank->description));
            $extractedSequences = $this->extractDigitSequences((string) $bank->description);

            foreach ($manualTransactions as $manual) {
                $isManualIncome = $manual->type === 'income';
                $isBankIncome = $bankAmount > 0;
                if ($isManualIncome !== $isBankIncome) {
                    continue;
                }

                $amountMatches = abs((float) $manual->amount - abs($bankAmount)) < 0.01;
                $dateMatches = abs($manual->date->diffInDays($bank->date)) <= 3;

                if (!$amountMatches || !$dateMatches) {
                    continue;
                }

                $manualCpf = preg_replace('/[^0-9]/', '', $manual->member->cpf ?? '');
                $cpfMatches = false;
                if (strlen($manualCpf) >= 6) {
                    foreach ($extractedSequences as $seq) {
                        if (str_contains($seq, $manualCpf) || str_contains($manualCpf, $seq)) {
                            $cpfMatches = true;
                            break;
                        }
                    }
                }

                $nameMatches = false;
                if ($manual->member) {
                    $memberName = Str::ascii(strtolower((string) $manual->member->name));
                    $firstName = explode(' ', $memberName)[0] ?? '';
                    if (strlen($firstName) > 3 && str_contains($bankDescNormalized, $firstName)) {
                        $nameMatches = true;
                    }
                }

                if ($cpfMatches || $nameMatches) {
                    $bank->auto_match = [
                        'manual_id' => $manual->id,
                        'manual_description' => $manual->description,
                        'member_name' => $manual->member->name ?? null,
                        'confidence' => $cpfMatches ? 100 : 85,
                    ];
                    return $bank;
                }
            }

            if ($bankAmount > 0) {
                foreach ($allMembers as $member) {
                    $memberCpf = preg_replace('/[^0-9]/', '', $member->cpf ?? '');
                    if (strlen($memberCpf) < 6) {
                        continue;
                    }

                    foreach ($extractedSequences as $seq) {
                        if (str_contains($seq, $memberCpf) || str_contains($memberCpf, $seq)) {
                            $bank->suggested_member = [
                                'id' => $member->id,
                                'name' => $member->name,
                                'cpf' => $member->cpf,
                                'type' => 'income',
                                'confidence' => 100,
                            ];
                            return $bank;
                        }
                    }

                    $memberName = Str::ascii(strtolower((string) $member->name));
                    $firstName = explode(' ', $memberName)[0] ?? '';
                    if (strlen($firstName) > 3 && str_contains($bankDescNormalized, $firstName)) {
                        $bank->suggested_member = [
                            'id' => $member->id,
                            'name' => $member->name,
                            'cpf' => $member->cpf,
                            'type' => 'income',
                            'confidence' => 85,
                        ];
                        return $bank;
                    }
                }
            }

            return $bank;
        });

        return response()->json([
            'bank_entries' => $bankTransactions,
            'manual_entries' => $manualTransactions,
        ]);
    }

    public function bulkCreateAndMatch(Request $request): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $request->validate([
            'items' => 'required|array',
            'items.*.bank_transaction_id' => 'required|exists:bank_transactions,id',
            'items.*.member_id' => 'required|exists:members,id',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.society_id' => 'nullable|exists:societies,id',
        ]);

        $items = $request->items;
        $successCount = 0;
        $allowedSocietyIds = $this->allowedSocietyIds($request);

        try {
            DB::transaction(function () use ($items, &$successCount, $allowedSocietyIds) {
                foreach ($items as $item) {
                    $bankTransaction = BankTransaction::findOrFail($item['bank_transaction_id']);

                    if ($bankTransaction->status === 'reconciled') {
                        continue;
                    }

                    $societyId = $item['society_id'] ?? null;
                    if ($societyId && !in_array((int) $societyId, $allowedSocietyIds, true)) {
                        abort(403, 'Sociedade nao autorizada para conciliacao.');
                    }

                    Transaction::create([
                        'type' => $bankTransaction->amount > 0 ? 'income' : 'expense',
                        'amount' => abs($bankTransaction->amount),
                        'description' => 'Lancamento via Extrato: '.$bankTransaction->description,
                        'date' => $bankTransaction->date,
                        'payment_method' => 'transfer',
                        'member_id' => $item['member_id'],
                        'category_id' => $item['category_id'] ?? Category::where('name', 'like', '%Dizimo%')->first()?->id,
                        'society_id' => $societyId,
                        'status' => 'confirmed',
                        'reconciled_at' => now(),
                        'reconciled_by' => auth()->id(),
                        'bank_transaction_id' => $bankTransaction->id,
                    ]);

                    $bankTransaction->update(['status' => 'reconciled']);
                    $successCount++;
                }
            });

            return response()->json([
                'message' => "{$successCount} lancamentos criados e conciliados com sucesso!",
                'count' => $successCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Bank reconciliation bulk create/match failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Erro ao realizar lancamento em lote',
            ], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $allowedSocietyIds = $this->allowedSocietyIds($request);

        $history = Transaction::whereNotNull('bank_transaction_id')
            ->where(function ($q) use ($allowedSocietyIds) {
                $q->whereNull('society_id');
                if (!empty($allowedSocietyIds)) {
                    $q->orWhereIn('society_id', $allowedSocietyIds);
                }
            })
            ->with(['category', 'reconciledBy', 'bankTransaction.statement', 'member'])
            ->orderBy('reconciled_at', 'desc')
            ->paginate(50);

        return response()->json($history);
    }

    public function match(Request $request): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $request->validate([
            'manual_transaction_id' => 'required|exists:transactions,id',
            'bank_transaction_id' => 'required|exists:bank_transactions,id',
        ]);

        DB::transaction(function () use ($request) {
            $manual = Transaction::findOrFail($request->manual_transaction_id);
            $this->ensureTransactionScope($request, $manual);

            $bank = BankTransaction::findOrFail($request->bank_transaction_id);

            $manual->update([
                'bank_transaction_id' => $bank->id,
                'reconciled_at' => now(),
                'reconciled_by' => Auth::id(),
                'status' => 'confirmed',
            ]);

            $bank->update(['status' => 'reconciled']);
        });

        return response()->json(['message' => 'Conciliacao realizada com sucesso']);
    }

    public function bulkMatch(Request $request): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $request->validate([
            'matches' => 'required|array',
            'matches.*.manual_transaction_id' => 'required|exists:transactions,id',
            'matches.*.bank_transaction_id' => 'required|exists:bank_transactions,id',
        ]);

        $matches = $request->matches;
        $successCount = 0;

        try {
            DB::transaction(function () use ($matches, &$successCount, $request) {
                foreach ($matches as $match) {
                    $manualTransaction = Transaction::findOrFail($match['manual_transaction_id']);
                    $this->ensureTransactionScope($request, $manualTransaction);

                    $bankTransaction = BankTransaction::findOrFail($match['bank_transaction_id']);

                    if ($manualTransaction->bank_transaction_id || $bankTransaction->status === 'reconciled') {
                        continue;
                    }

                    $manualTransaction->update([
                        'bank_transaction_id' => $bankTransaction->id,
                        'status' => 'confirmed',
                        'reconciled_at' => now(),
                        'reconciled_by' => auth()->id(),
                    ]);

                    $bankTransaction->update(['status' => 'reconciled']);
                    $successCount++;
                }
            });

            return response()->json([
                'message' => "{$successCount} transacoes conciliadas com sucesso!",
                'count' => $successCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Bank reconciliation bulk match failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Erro ao realizar conciliacao em lote',
            ], 500);
        }
    }

    public function ignore(BankTransaction $bankTransaction): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        $bankTransaction->update(['status' => 'ignored']);
        return response()->json(['message' => 'Transacao do extrato ignorada']);
    }

    public function destroyBankTransaction(BankTransaction $bankTransaction): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);

        if ($bankTransaction->status !== 'pending') {
            return response()->json([
                'message' => 'Nao e possivel excluir uma transacao que ja foi conciliada ou ignorada.',
            ], 422);
        }

        $bankTransaction->delete();

        return response()->json([
            'message' => 'Transacao do extrato removida com sucesso.',
        ]);
    }

    public function unmatch(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('reconcile', FinancialAccess::class);
        $this->ensureTransactionScope($request, $transaction);

        DB::transaction(function () use ($transaction) {
            if ($transaction->bank_transaction_id) {
                BankTransaction::where('id', $transaction->bank_transaction_id)
                    ->update(['status' => 'pending']);

                $transaction->update([
                    'bank_transaction_id' => null,
                    'reconciled_at' => null,
                    'reconciled_by' => null,
                ]);
            }
        });

        return response()->json(['message' => 'Conciliacao desfeita']);
    }

    private function extractDigitSequences(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $digitsOnly = preg_replace('/[^0-9]/', '', $text);
        if (strlen($digitsOnly) >= 6) {
            return [$digitsOnly];
        }

        return [];
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
}
