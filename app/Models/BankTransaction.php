<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankTransaction extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'date',
        'amount',
        'description',
        'bank_ref',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'bank_transaction_id');
    }
}
