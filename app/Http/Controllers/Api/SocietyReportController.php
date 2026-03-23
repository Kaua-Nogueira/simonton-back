<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ChurchConfigController;
use App\Models\Society;
use App\Models\SocietyFinancialMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SocietyReportController extends Controller
{
    /**
     * Gera o balancete mensal consolidado para uma Sociedade Interna.
     */
    public function societyBalancete(Request $request)
    {
        $validated = $request->validate([
            'society_id' => 'required|exists:societies,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $society = Society::findOrFail($validated['society_id']);
        $date = Carbon::create($validated['year'], $validated['month'], 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 1. Saldo Anterior (Total de Entradas - Total de Saídas antes do mês corrente)
        $saldoAnterior = SocietyFinancialMovement::where('society_id', $society->id)
            ->where('date', '<', $startOfMonth)
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as total")
            ->value('total') ?? 0;

        // 2. Movimentações do Mês (Agrupadas por Categoria para o corpo do balancete)
        $movimentacoes = SocietyFinancialMovement::where('society_id', $society->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // 3. Resumo por Categoria (para o demonstrativo)
        $resumoCategorias = $movimentacoes->groupBy(['category', 'type'])->map(function ($items, $category) {
            return $items->map(function ($typeGroup) {
                return $typeGroup->sum('amount');
            });
        });

        $totalEntradas = $movimentacoes->where('type', 'income')->sum('amount');
        $totalSaidas = $movimentacoes->where('type', 'expense')->sum('amount');
        $saldoAtual = ($saldoAnterior + $totalEntradas) - $totalSaidas;

        $data = [
            'society' => $society,
            'church' => [
                'name' => ChurchConfigController::get('org_name', 'Igreja Presbiteriana Simonton'),
                'cnpj' => ChurchConfigController::get('org_cnpj', '00.000.000/0001-00'),
                'address' => ChurchConfigController::get('org_address', 'Endereço não configurado'),
            ],
            'periodo' => $date->translatedFormat('F / Y'),
            'saldoAnterior' => $saldoAnterior,
            'movimentacoes' => $movimentacoes->sortBy('date'),
            'resumoCategorias' => $resumoCategorias,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'saldoAtual' => $saldoAtual,
            'dataEmissao' => now()->format('d/m/Y H:i'),
        ];

        // Se o usuário pedir preview via query, retorna JSON (útil para debug)
        if ($request->has('preview')) {
            return response()->json($data);
        }

        $pdf = Pdf::loadView('reports.society-balancete', $data);
        
        return $pdf->download("balancete_{$society->name}_{$validated['month']}_{$validated['year']}.pdf");
    }
}
