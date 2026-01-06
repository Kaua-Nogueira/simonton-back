<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryEntry extends Model
{
    protected $fillable = [
        'date',
        'status',
        'total_amount',
        'notes',
        'user_id',
        'confirmed_by'
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cash()
    {
        return $this->hasMany(TreasuryCash::class, 'entry_id');
    }

    public function splits()
    {
        return $this->hasMany(TreasurySplit::class, 'entry_id');
    }
}
