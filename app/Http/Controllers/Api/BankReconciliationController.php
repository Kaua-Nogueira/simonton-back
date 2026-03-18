<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\Transaction;
use App\Services\OFXImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'total_entries' => count($importedData),
                'start_date' => collect($importedData)->min('date'),
                'end_date' => collect($importedData)->max('date'),
            ]);

            foreach ($importedData as $data) {
                BankTransaction::create([
                    'bank_statement_id' => $statement->id,
                    'date' => $data['date'],
                    'amount' => $data['amount'],
                    'description' => $data['description'],
                    'bank_ref' => $data['ofx_data'] ?? null,
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

        $manualTransactions = Transaction::where('status', 'confirmed')
            ->whereNull('bank_transaction_id')
            ->with(['category', 'member'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'bank_entries' => $bankTransactions,
            'manual_entries' => $manualTransactions
        ]);
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
                'reconciled_by' => \Illuminate\Support\Facades\Auth::id()
            ]);

            $bank->update(['status' => 'reconciled']);
        });

        return response()->json(['message' => 'Conciliação realizada com sucesso']);
    }

    public function ignore(BankTransaction $bankTransaction): JsonResponse
    {
        $bankTransaction->update(['status' => 'ignored']);
        return response()->json(['message' => 'Transação do extrato ignorada']);
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
