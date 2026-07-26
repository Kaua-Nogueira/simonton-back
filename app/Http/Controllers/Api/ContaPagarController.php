<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContaPagar;
use App\Services\ContaPagarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ContaPagarController extends Controller
{
    public function __construct(private ContaPagarService $service) {}

    public function index(Request $request): JsonResponse
    {
        // Garante que o status de todas as contas esteja atualizado antes de listar
        $this->service->checkAndMarkVencidas();
        $this->service->generateNextMonthlyInstances();

        $query = ContaPagar::with(['category', 'costCenter', 'transaction.reconciliation.items'])
            ->orderBy('data_vencimento', 'asc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->mes && $request->ano) {
            $query->whereYear('data_vencimento', $request->ano)
                  ->whereMonth('data_vencimento', $request->mes);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'descricao'       => 'required|string|max:255',
            'valor'           => 'required|numeric|min:0.01',
            'data_vencimento' => 'required|date',
            'category_id'     => 'required|exists:categories,id',
            'cost_center_id'  => 'nullable|exists:cost_centers,id',
            'budget_item_id'  => 'nullable|exists:budget_items,id',
            'recorrente'      => 'boolean',
            'tipo_recorrencia'=> 'nullable|in:mensal,anual,personalizado',
            'dia_vencimento'  => 'nullable|integer|between:1,31',
            'data_inicio'     => 'nullable|date',
            'data_fim'        => 'nullable|date|after_or_equal:data_inicio',
            'meses'           => 'nullable|integer|min:1|max:60',
        ]);

        $data['user_id'] = auth()->id();

        if (!empty($data['recorrente'])) {
            $data['serie_id'] = Str::uuid()->toString();
        }

        $conta = ContaPagar::create($data);

        $generated = [];
        if ($conta->recorrente) {
            $generated = $this->service->generateRecorrencia($conta, $data['meses'] ?? 12);
        }

        return response()->json([
            'message'   => 'Conta criada com sucesso',
            'data'      => $conta->load(['category', 'costCenter']),
            'generated' => count($generated),
        ], 201);
    }

    public function show(ContaPagar $contaPagar): JsonResponse
    {
        return response()->json($contaPagar->load(['category', 'costCenter', 'transaction.reconciliation.items', 'budgetItem']));
    }

    public function update(Request $request, ContaPagar $contaPagar): JsonResponse
    {
        $data = $request->validate([
            'descricao'       => 'string|max:255',
            'valor'           => 'numeric|min:0.01',
            'data_vencimento' => 'date',
            'category_id'     => 'exists:categories,id',
            'cost_center_id'  => 'nullable|exists:cost_centers,id',
            'status'          => 'in:pendente,vencido',
            'toda_serie'      => 'boolean',
        ]);

        $todaSerie = $data['toda_serie'] ?? false;
        unset($data['toda_serie']);

        if ($todaSerie && $contaPagar->serie_id) {
            ContaPagar::where('serie_id', $contaPagar->serie_id)
                ->where('status', '!=', 'pago')
                ->update($data);
        } else {
            $contaPagar->update($data);
        }

        return response()->json(['message' => 'Atualizado com sucesso', 'data' => $contaPagar->fresh()]);
    }

    public function destroy(Request $request, ContaPagar $contaPagar): JsonResponse
    {
        $todaSerie = $request->boolean('toda_serie');

        if ($todaSerie && $contaPagar->serie_id) {
            $series = ContaPagar::where('serie_id', $contaPagar->serie_id)->get();

            // Delete linked transactions first
            $transactionIds = $series->pluck('transaction_id')->filter();
            if ($transactionIds->isNotEmpty()) {
                \App\Models\Transaction::whereIn('id', $transactionIds)->delete();
            }

            $series->each->delete();
        } else {
            // Delete linked transaction if conta was paid
            if ($contaPagar->transaction_id) {
                \App\Models\Transaction::find($contaPagar->transaction_id)?->delete();
            }

            $contaPagar->delete();
        }

        return response()->json(['message' => 'Removido com sucesso']);
    }

    public function pay(Request $request, ContaPagar $contaPagar): JsonResponse
    {
        $request->validate([
            'data_pagamento' => 'nullable|date',
        ]);

        $transaction = $this->service->pay($contaPagar, $request->data_pagamento);

        return response()->json([
            'message'     => 'Conta paga com sucesso',
            'data'        => $contaPagar->fresh()->load(['category', 'transaction']),
            'transaction' => $transaction,
        ]);
    }

    public function dashboard(): JsonResponse
    {
        // Atualiza status e gera próximas instâncias antes de calcular os totais
        $this->service->checkAndMarkVencidas();
        $this->service->generateNextMonthlyInstances();

        $now = Carbon::now();

        // 1. Total Pendente (Tudo que venceu no passado e ainda não foi pago + o que vence este mês)
        $totalPendente = ContaPagar::whereIn('status', ['pendente', 'vencido'])
            ->where('data_vencimento', '<=', $now->copy()->endOfMonth())
            ->sum('valor');

        // 2. Total Pago (Tudo que foi pago DENTRO deste mês atual, independente da data de vencimento)
        // Isso reflete o fluxo de caixa real do mês
        $totalPago = ContaPagar::where('status', 'pago')
            ->whereHas('transaction', function ($q) use ($now) {
                $q->whereYear('date', $now->year)
                  ->whereMonth('date', $now->month);
            })->sum('valor');
            
        // Fallback: se houver conta paga sem transação (backup), olhamos pela data de vencimento do mês
        $totalPagoManual = ContaPagar::where('status', 'pago')
            ->whereNull('transaction_id')
            ->whereYear('data_vencimento', $now->year)
            ->whereMonth('data_vencimento', $now->month)
            ->sum('valor');
        
        $totalPago += $totalPagoManual;

        // 3. Vencidas (Global)
        $totalVencido = ContaPagar::where('status', 'vencido')->sum('valor');
        $countVencidas = ContaPagar::where('status', 'vencido')->count();

        // Próximos 7 dias
        $aVencer = ContaPagar::with('category')
            ->where('status', 'pendente')
            ->whereBetween('data_vencimento', [Carbon::today(), Carbon::today()->addDays(7)])
            ->orderBy('data_vencimento')
            ->get();

        return response()->json([
            'total_pendente' => (float) $totalPendente,
            'total_pago'     => (float) $totalPago,
            'total_vencido'  => (float) $totalVencido,
            'count_vencidas' => $countVencidas,
            'a_vencer'       => $aVencer,
        ]);
    }

    public function uploadAttachment(Request $request, ContaPagar $contaPagar): JsonResponse
    {
        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($contaPagar->attachment_path) {
            Storage::disk('local')->delete($contaPagar->attachment_path);
        }

        $path = $request->file('attachment')->store('contas-pagar/attachments', 'local');
        $contaPagar->update(['attachment_path' => $path]);

        return response()->json([
            'message' => 'Arquivo anexado com sucesso',
            'data' => $contaPagar->fresh()->load(['category', 'costCenter', 'transaction.reconciliation.items'])
        ]);
    }

    public function deleteAttachment(Request $request, ContaPagar $contaPagar): JsonResponse
    {
        if ($contaPagar->attachment_path) {
            Storage::disk('local')->delete($contaPagar->attachment_path);
            $contaPagar->update(['attachment_path' => null]);
        }

        return response()->json([
            'message' => 'Anexo removido com sucesso',
            'data' => $contaPagar->fresh()->load(['category', 'costCenter', 'transaction.reconciliation.items'])
        ]);
    }

    public function viewAttachment(Request $request, ContaPagar $contaPagar)
    {
        if (!$contaPagar->attachment_path || !Storage::disk('local')->exists($contaPagar->attachment_path)) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        return response()->file(Storage::disk('local')->path($contaPagar->attachment_path), [
            'Content-Disposition' => 'inline; filename="'.basename($contaPagar->attachment_path).'"',
        ]);
    }
}
