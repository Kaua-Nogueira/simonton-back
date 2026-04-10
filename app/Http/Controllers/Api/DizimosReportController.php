<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DizimosReportController extends Controller
{
    /**
     * Retorna o relatório de Dízimos e Ofertas agrupados por Mês para o ano requisitado.
     */
    public function report(Request $request): JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        
        // Pega as contas de Dízimos e Ofertas (Usando `name` parecido, ou assumindo que existe)
        // O ideal é buscar pelas subcontas onde o nome tem 'dizimo' ou 'oferta'
        $categories = Category::where('type', 'income')
            ->where(function($q) {
                $q->where('name', 'LIKE', '%dízimo%')
                  ->orWhere('name', 'LIKE', '%dizimo%')
                  ->orWhere('name', 'LIKE', '%oferta%');
            })->pluck('id');

        // 1. Pegar transações confirmadas que NÃO foram divididas
        $directTransactions = Transaction::whereIn('category_id', $categories)
            ->where('status', 'confirmed')
            ->whereYear('date', $year)
            ->with(['member', 'category'])
            ->get();

        // 2. Pegar divisões (splits) de transações divididas que estão nas categorias
        $splitData = \App\Models\TransactionSplit::whereIn('category_id', $categories)
            ->whereHas('transaction', function($q) use ($year) {
                $q->where('status', 'split')
                  ->whereYear('date', $year);
            })
            ->with(['member', 'category', 'transaction'])
            ->get();

        // Agrupamento manual por Mês e por Categoria
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = [
                'month' => $i,
                'dizimos' => 0,
                'ofertas' => 0,
                'total' => 0
            ];
        }

        // Processar transações diretas
        foreach ($directTransactions as $t) {
            $month = Carbon::parse($t->date)->month;
            $amount = (float) $t->amount;
            
            $isDizimo = stripos($t->category->name, 'dizimo') !== false || stripos($t->category->name, 'dízimo') !== false;
            
            if ($isDizimo) {
                $monthlyData[$month]['dizimos'] += $amount;
            } else {
                $monthlyData[$month]['ofertas'] += $amount;
            }
            
            $monthlyData[$month]['total'] += $amount;
        }

        // Processar divisões
        foreach ($splitData as $split) {
            $month = Carbon::parse($split->transaction->date)->month;
            $amount = (float) $split->amount;
            
            $isDizimo = stripos($split->category->name, 'dizimo') !== false || stripos($split->category->name, 'dízimo') !== false;
            
            if ($isDizimo) {
                $monthlyData[$month]['dizimos'] += $amount;
            } else {
                $monthlyData[$month]['ofertas'] += $amount;
            }
            
            $monthlyData[$month]['total'] += $amount;
        }

        // Para as transações recentes, vamos unificar as duas fontes para exibição
        $latest = collect();
        foreach ($directTransactions as $t) {
            $latest->push([
                'id' => $t->id,
                'date' => $t->date,
                'amount' => $t->amount,
                'description' => $t->description,
                'member' => $t->member,
                'category' => $t->category,
                'is_split' => false
            ]);
        }
        foreach ($splitData as $s) {
            $latest->push([
                'id' => $s->transaction_id . '-' . $s->id,
                'date' => $s->transaction->date,
                'amount' => $s->amount,
                'description' => $s->transaction->description . ' (Parte)',
                'member' => $s->member,
                'category' => $s->category,
                'is_split' => true
            ]);
        }

        return response()->json([
            'year' => $year,
            'summary' => array_values($monthlyData),
            'latest_transactions' => $latest->sortByDesc('date')->take(20)->values()
        ]);
    }
}
