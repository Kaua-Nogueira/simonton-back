<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'source_item_id',
        'target_item_id',
        'amount',
        'description',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function sourceItem()
    {
        return $this->belongsTo(BudgetItem::class, 'source_item_id');
    }

    public function targetItem()
    {
        return $this->belongsTo(BudgetItem::class, 'target_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
