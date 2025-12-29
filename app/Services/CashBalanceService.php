<?php

namespace App\Services;

use App\Models\CashBalance;
use App\Models\Transaction;
use Carbon\Carbon;

class CashBalanceService
{
    public function updateBalanceForDate(Carbon $date): CashBalance
    {
        $balance = CashBalance::firstOrCreate(
            ['date' => $date],
            [
                'opening_balance' => $this->getPreviousDayClosingBalance($date),
                'total_income' => 0,
                'total_expense' => 0,
                'closing_balance' => 0,
            ]
        );

        // Calculate totals for the day
        $totals = Transaction::whereDate('date', $date)
            ->where('status', 'confirmed')
            ->selectRaw('
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense
            ')
            ->first();

        $balance->total_income = $totals->total_income ?? 0;
        $balance->total_expense = $totals->total_expense ?? 0;
        $balance->calculateClosingBalance();

        return $balance;
    }

    public function recalculateFromDate(Carbon $startDate): void
    {
        $currentDate = $startDate->copy();
        $today = Carbon::today();

        while ($currentDate->lte($today)) {
            $this->updateBalanceForDate($currentDate);
            $currentDate->addDay();
        }
    }

    private function getPreviousDayClosingBalance(Carbon $date): float
    {
        $previousDate = $date->copy()->subDay();
        
        $previousBalance = CashBalance::where('date', $previousDate)->first();
        
        return $previousBalance ? (float) $previousBalance->closing_balance : 0;
    }

    public function getCurrentBalance(): float
    {
        $latestBalance = CashBalance::orderBy('date', 'desc')->first();
        
        return $latestBalance ? (float) $latestBalance->closing_balance : 0;
    }
}
