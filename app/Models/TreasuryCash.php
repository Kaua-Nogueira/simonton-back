<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryCash extends Model
{
    protected $table = 'treasury_cash';

    protected $fillable = [
        'entry_id',
        'denomination',
        'quantity',
        'amount',
    ];

    protected $casts = [
        'denomination' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function entry()
    {
        return $this->belongsTo(TreasuryEntry::class, 'entry_id');
    }
}
