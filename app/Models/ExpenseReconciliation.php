<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseReconciliation extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'responsible_member_id',
        'total_advanced',
        'total_reconciled',
        'status',
        'notes',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'total_advanced' => 'decimal:2',
        'total_reconciled' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function responsibleMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'responsible_member_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseReconciliationItem::class, 'reconciliation_id');
    }

    public function getBalanceAttribute()
    {
        return $this->total_advanced - $this->total_reconciled;
    }
}
