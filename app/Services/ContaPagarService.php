<?php

namespace App\Services;

use App\Models\ContaPagar;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContaPagarService
{
    /**
     * Pay a conta: creates a corresponding expense Transaction and updates status.
     */
    public function pay(ContaPagar $conta, ?string $dataPagamento = null): Transaction
    {
        if ($conta->status === 'pago') {
            throw new \RuntimeException('Esta conta já foi paga.');
        }

        $date = $dataPagamento ? Carbon::parse($dataPagamento) : Carbon::today();

        $transaction = Transaction::create([
            'type'        => 'expense',
            'amount'      => $conta->valor,
            'description' => $conta->descricao,
            'date'        => $date,
            'category_id' => $conta->category_id,
            'cost_center_id' => $conta->cost_center_id,
            'status'      => 'confirmed',
            'payment_method' => 'transferencia',
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id(),
        ]);

        $conta->update([
            'status'         => 'pago',
            'transaction_id' => $transaction->id,
        ]);

        return $transaction;
    }

    /**
     * Generates all monthly instances of a recurring conta.
     * Uses serie_id to avoid duplicates.
     */
    public function generateRecorrencia(ContaPagar $seed, int $months = 12): array
    {
        if (!$seed->recorrente) {
            return [];
        }

        $serieId = $seed->serie_id ?? Str::uuid()->toString();

        // Update seed with serie_id if not set
        if (!$seed->serie_id) {
            $seed->update(['serie_id' => $serieId]);
        }

        $start     = $seed->data_inicio ?? $seed->data_vencimento;
        $end       = $seed->data_fim;
        $day       = $seed->dia_vencimento ?? $start->day;
        $generated = [];

        for ($i = 1; $i <= $months; $i++) {
            $due = $start->copy()->addMonths($i)->setDay(min($day, $start->copy()->addMonths($i)->daysInMonth));

            if ($end && $due->gt($end)) {
                break;
            }

            // Avoid duplicates: check if instance already exists for this month in the series
            $exists = ContaPagar::where('serie_id', $serieId)
                ->whereYear('data_vencimento', $due->year)
                ->whereMonth('data_vencimento', $due->month)
                ->exists();

            if ($exists) {
                continue;
            }

            $generated[] = ContaPagar::create([
                'descricao'              => $seed->descricao,
                'valor'                  => $seed->valor,
                'data_vencimento'        => $due,
                'category_id'            => $seed->category_id,
                'cost_center_id'         => $seed->cost_center_id,
                'budget_item_id'         => $seed->budget_item_id,
                'status'                 => 'pendente',
                'recorrente'             => false,
                'serie_id'               => $serieId,
                'gerado_automaticamente' => true,
                'user_id'                => $seed->user_id,
            ]);
        }

        return $generated;
    }

    /**
     * Marks overdue pending contas as 'vencido'.
     */
    public function checkAndMarkVencidas(): int
    {
        return ContaPagar::where('status', 'pendente')
            ->where('data_vencimento', '<', Carbon::today())
            ->update(['status' => 'vencido']);
    }

    /**
     * Generates the next monthly instance for all active recurring series
     * (ensures next month always has a pending conta).
     */
    public function generateNextMonthlyInstances(): int
    {
        $count = 0;
        $nextMonth = Carbon::today()->addMonth();

        // Find series that have at least one instance and the seed (recorrente = true)
        $series = ContaPagar::where('recorrente', true)
            ->whereNotNull('serie_id')
            ->get();

        foreach ($series as $seed) {
            $exists = ContaPagar::where('serie_id', $seed->serie_id)
                ->whereYear('data_vencimento', $nextMonth->year)
                ->whereMonth('data_vencimento', $nextMonth->month)
                ->exists();

            if (!$exists) {
                $end = $seed->data_fim;
                $day = $seed->dia_vencimento ?? $seed->data_vencimento->day;
                $due = $nextMonth->copy()->setDay(min($day, $nextMonth->daysInMonth));

                if ($end && $due->gt($end)) {
                    continue;
                }

                ContaPagar::create([
                    'descricao'              => $seed->descricao,
                    'valor'                  => $seed->valor,
                    'data_vencimento'        => $due,
                    'category_id'            => $seed->category_id,
                    'cost_center_id'         => $seed->cost_center_id,
                    'budget_item_id'         => $seed->budget_item_id,
                    'status'                 => 'pendente',
                    'recorrente'             => false,
                    'serie_id'               => $seed->serie_id,
                    'gerado_automaticamente' => true,
                    'user_id'                => $seed->user_id,
                ]);
                $count++;
            }
        }

        return $count;
    }
}
