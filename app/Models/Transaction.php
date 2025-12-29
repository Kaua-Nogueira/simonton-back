<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'description',
        'date',
        'payment_method',
        'member_id',
        'category_id',
        'cost_center_id',
        'status',
        'suggestion_confidence',
        'parent_transaction_id',
        'ofx_data',
        'balance_before',
        'balance_after',
        'reconciled_by',
        'reconciled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function splitTransactions(): HasMany
    {
        return $this->hasMany(TransactionSplit::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuggested($query)
    {
        return $query->where('status', 'suggested');
    }

    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'split']);
    }

    public function hasHighConfidence(): bool
    {
        return $this->suggestion_confidence && $this->suggestion_confidence >= 80;
    }
}
