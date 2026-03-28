<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Category;
use App\Models\Society;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\Transaction;
use App\Services\OFXImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BankReconciliationController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'bank_name' => 'nullable|string'
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
                
                // Check for duplicates
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
            'statement' => $statement->load('transactions')
        ], 201);
    }

    public function pending(): JsonResponse
    {
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
            ->with(['category', 'member', 'society'])
            ->orderBy('date', 'desc')
            ->get();

        $allMembers = Member::whereNotNull('cpf')->get();

        $bankTransactions = $bankTransactions->map(function ($bank) use ($manualTransactions, $allMembers) {
            $bankAmount = (float) $bank->amount;
            
            $bankDescNormalized = \Illuminate\Support\Str::ascii(strtolower($bank->description));
            $extractedSequences = $this->extractDigitSequences($bank->description);
            
            // First: Look for matches with existing manual entries
            foreach ($manualTransactions as $manual) {
                // Ensure same direction (both income or both expense)
                $isManualIncome = $manual->type === 'income';
                $isBankIncome = $bankAmount > 0;
                if ($isManualIncome !== $isBankIncome) continue;

                $amountMatches = abs((float)$manual->amount - abs($bankAmount)) < 0.01;
                $dateMatches = abs($manual->date->diffInDays($bank->date)) <= 3;
                
                if (!$amountMatches || !$dateMatches) continue;

                // 1. CPF Match (Strongest)
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

                // 2. Name Match (Strong Fallback)
                $nameMatches = false;
                if ($manual->member) {
                    $memberName = \Illuminate\Support\Str::ascii(strtolower($manual->member->name));
                    $nameParts = explode(' ', $memberName);
                    $firstName = $nameParts[0] ?? '';
                    if (strlen($firstName) > 3 && str_contains($bankDescNormalized, $firstName)) {
                        $nameMatches = true;
                    }
                }

                if ($cpfMatches || $nameMatches) {
                    $bank->auto_match = [
                        'manual_id' => $manual->id,
                        'manual_description' => $manual->description,
                        'member_name' => $manual->member->name,
                        'confidence' => $cpfMatches ? 100 : 85,
                    ];
                    return $bank;
                }
            }

            // Second: If it's a positive amount (potential tithes) and no manual match found,
            // try to identify the member purely from bank description
            if ($bankAmount > 0) {
                foreach ($allMembers as $member) {
                    $memberCpf = preg_replace('/[^0-9]/', '', $member->cpf ?? '');
                    if (strlen($memberCpf) < 6) continue;

                    foreach ($extractedSequences as $seq) {
                        if (str_contains($seq, $memberCpf) || str_contains($memberCpf, $seq)) {
                            $bank->suggested_member = [
                                'id' => $member->id,
                                'name' => $member->name,
                                'cpf' => $member->cpf,
                                'type' => 'income',
                                'confidence' => 100
                            ];
                            return $bank;
                        }
                    }
                    
                    // Fallback to name match for members
                    $memberName = \Illuminate\Support\Str::ascii(strtolower($member->name));
                    $nameParts = explode(' ', $memberName);
                    $firstName = $nameParts[0] ?? '';
                    if (strlen($firstName) > 3 && str_contains($bankDescNormalized, $firstName)) {
                        $bank->suggested_member = [
                            'id' => $member->id,
                            'name' => $member->name,
                            'cpf' => $member->cpf,
                            'type' => 'income',
                            'confidence' => 85
                        ];
                        return $bank;
                    }
                }
            }

            return $bank;
        });

        return response()->json([
            'bank_entries' => $bankTransactions,
            'manual_entries' => $manualTransactions
        ]);
    }

    public function bulkCreateAndMatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.bank_transaction_id' => 'required|exists:bank_transactions,id',
            'items.*.member_id' => 'required|exists:members,id',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.society_id' => 'nullable|exists:societies,id',
        ]);

        $items = $request->items;
        $successCount = 0;

        try {
            DB::transaction(function () use ($items, &$successCount) {
                foreach ($items as $item) {
                    $bankTransaction = BankTransaction::findOrFail($item['bank_transaction_id']);
                    
                    if ($bankTransaction->status === 'reconciled') continue;

                    // Create new manual transaction
                    $manualTransaction = Transaction::create([
                        'type' => $bankTransaction->amount > 0 ? 'income' : 'expense',
                        'amount' => abs($bankTransaction->amount),
                        'description' => "Lançamento via Extrato: " . $bankTransaction->description,
                        'date' => $bankTransaction->date,
                        'payment_method' => 'transfer',
                        'member_id' => $item['member_id'],
                        'category_id' => $item['category_id'] ?? Category::where('name', 'like', '%Dízimo%')->first()?->id,
                        'society_id' => $item['society_id'] ?? 1, // Default society
                        'status' => 'confirmed',
                        'reconciled_at' => now(),
                        'reconciled_by' => auth()->id(),
                        'bank_transaction_id' => $bankTransaction->id
                    ]);

                    $bankTransaction->update([
                        'status' => 'reconciled'
                    ]);

                    $successCount++;
                }
            });

            return response()->json([
                'message' => "{$successCount} lançamentos criados e conciliados com sucesso!",
                'count' => $successCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar lançamento em lote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(): JsonResponse
    {
        $history = Transaction::whereNotNull('bank_transaction_id')
            ->with(['category', 'reconciledBy', 'bankTransaction.statement', 'member'])
            ->orderBy('reconciled_at', 'desc')
            ->get();

        return response()->json($history);
    }

    private function extractDigitSequences($text): array
    {
        if (empty($text)) return [];
        
        $digitsOnly = preg_replace('/[^0-9]/', '', $text);
        if (strlen($digitsOnly) >= 6) {
            return [$digitsOnly];
        }
        
        return [];
    }

    public function match(Request $request): JsonResponse
    {
        $request->validate([
            'manual_transaction_id' => 'required|exists:transactions,id',
            'bank_transaction_id' => 'required|exists:bank_transactions,id',
        ]);

        DB::transaction(function () use ($request) {
            $manual = Transaction::findOrFail($request->manual_transaction_id);
            $bank = BankTransaction::findOrFail($request->bank_transaction_id);

            $manual->update([
                'bank_transaction_id' => $bank->id,
                'reconciled_at' => now(),
                'reconciled_by' => Auth::id(),
                'status' => 'confirmed'
            ]);

            $bank->update(['status' => 'reconciled']);
        });

        return response()->json(['message' => 'Conciliação realizada com sucesso']);
    }

    public function bulkMatch(Request $request)
    {
        $request->validate([
            'matches' => 'required|array',
            'matches.*.manual_transaction_id' => 'required|exists:transactions,id',
            'matches.*.bank_transaction_id' => 'required|exists:bank_transactions,id',
        ]);

        $matches = $request->matches;
        $successCount = 0;

        try {
            DB::transaction(function () use ($matches, &$successCount) {
                foreach ($matches as $match) {
                    $manualTransaction = Transaction::findOrFail($match['manual_transaction_id']);
                    $bankTransaction = BankTransaction::findOrFail($match['bank_transaction_id']);

                    // Skip if already reconciled
                    if ($manualTransaction->bank_transaction_id || $bankTransaction->status === 'reconciled') {
                        continue;
                    }

                    // Perform matching
                    $manualTransaction->update([
                        'bank_transaction_id' => $bankTransaction->id,
                        'status' => 'confirmed',
                        'reconciled_at' => now(),
                        'reconciled_by' => auth()->id()
                    ]);

                    $bankTransaction->update([
                        'status' => 'reconciled'
                    ]);

                    $successCount++;
                }
            });

            return response()->json([
                'message' => "{$successCount} transações conciliadas com sucesso!",
                'count' => $successCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar conciliação em lote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ignore(BankTransaction $bankTransaction): JsonResponse
    {
        $bankTransaction->update(['status' => 'ignored']);
        return response()->json(['message' => 'Transação do extrato ignorada']);
    }

    public function destroyBankTransaction(BankTransaction $bankTransaction): JsonResponse
    {
        if ($bankTransaction->status !== 'pending') {
            return response()->json([
                'message' => 'Não é possível excluir uma transação que já foi conciliada ou ignorada.'
            ], 422);
        }

        $bankTransaction->delete();

        return response()->json([
            'message' => 'Transação do extrato removida com sucesso.'
        ]);
    }
    
    public function unmatch(Transaction $transaction): JsonResponse
    {
        DB::transaction(function () use ($transaction) {
            if ($transaction->bank_transaction_id) {
                BankTransaction::where('id', $transaction->bank_transaction_id)
                    ->update(['status' => 'pending']);
                
                $transaction->update([
                    'bank_transaction_id' => null,
                    'reconciled_at' => null,
                    'reconciled_by' => null
                ]);
            }
        });

        return response()->json(['message' => 'Conciliação desfeita']);
    }
}
