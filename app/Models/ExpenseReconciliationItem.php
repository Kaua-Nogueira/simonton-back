<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseReconciliationItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_id',
        'date',
        'description',
        'amount',
        'category_id',
        'cost_center_id',
        'document_number',
        'attachment_path',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(ExpenseReconciliation::class, 'reconciliation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
