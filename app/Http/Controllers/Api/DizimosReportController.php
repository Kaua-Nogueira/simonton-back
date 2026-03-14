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

        $transactions = Transaction::whereIn('category_id', $categories)
            ->where('status', 'confirmed')
            ->whereYear('date', $year)
            ->with(['member', 'category'])
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

        foreach ($transactions as $t) {
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

        return response()->json([
            'year' => $year,
            'summary' => array_values($monthlyData),
            'latest_transactions' => $transactions->sortByDesc('date')->take(20)->values()
        ]);
    }
}
