<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opening_balance',
        'total_income',
        'total_expense',
        'closing_balance',
        'is_reconciled',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'is_reconciled' => 'boolean',
    ];

    public function calculateClosingBalance(): void
    {
        $this->closing_balance = $this->opening_balance + $this->total_income - $this->total_expense;
        $this->save();
    }
}
