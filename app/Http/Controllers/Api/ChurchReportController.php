<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Controllers\Api\ChurchConfigController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ChurchReportController extends Controller
{
    /**
     * Gera o balancete mensal consolidado para a Igreja (Geral).
     */
    public function churchBalancete(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $date = Carbon::create($validated['year'], $validated['month'], 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 1. Saldo Anterior (Total de Entradas - Total de Saídas confirmadas antes do mês corrente)
        $saldoAnterior = Transaction::where('date', '<', $startOfMonth)
            ->whereIn('status', ['confirmed', 'reconciled', 'split'])
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as total")
            ->value('total') ?? 0;

        // 2. Movimentações do Mês (Agrupadas por Categoria para o corpo do balancete)
        // Buscamos as transações confirmadas no mês
        $transactions = Transaction::with('category')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['confirmed', 'reconciled', 'split'])
            ->get();

        // 3. Resumo por Categoria
        $resumoEntradas = $transactions->where('type', 'income')
            ->groupBy('category_id')
            ->map(function ($items) {
                return [
                    'category' => $items->first()->category->name ?? 'Sem Categoria',
                    'total' => $items->sum('amount')
                ];
            });

        $resumoSaidas = $transactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items) {
                return [
                    'category' => $items->first()->category->name ?? 'Sem Categoria',
                    'total' => $items->sum('amount')
                ];
            });

        $totalEntradas = $transactions->where('type', 'income')->sum('amount');
        $totalSaidas = $transactions->where('type', 'expense')->sum('amount');
        $saldoAtual = ($saldoAnterior + $totalEntradas) - $totalSaidas;

        $data = [
            'church' => [
                'name' => ChurchConfigController::get('org_name', 'Igreja Presbiteriana Simonton'),
                'cnpj' => ChurchConfigController::get('org_cnpj', '00.000.000/0001-00'),
                'address' => ChurchConfigController::get('org_address', 'Endereço não configurado'),
            ],
            'periodo' => $date->translatedFormat('F / Y'),
            'saldoAnterior' => $saldoAnterior,
            'resumoEntradas' => $resumoEntradas,
            'resumoSaidas' => $resumoSaidas,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'saldoAtual' => $saldoAtual,
            'dataEmissao' => now()->format('d/m/Y H:i'),
        ];

        if ($request->has('preview')) {
            return response()->json($data);
        }

        $pdf = Pdf::loadView('reports.church-balancete', $data);
        
        return $pdf->download("balancete_geral_{$validated['month']}_{$validated['year']}.pdf");
    }
}
