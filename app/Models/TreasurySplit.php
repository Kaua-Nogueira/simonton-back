<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasurySplit extends BaseModel
{
    protected $fillable = [
        'entry_id',
        'member_id',
        'society_id',
        'amount',
        'type',
        'is_digital',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_digital' => 'boolean',
    ];

    public function entry()
    {
        return $this->belongsTo(TreasuryEntry::class, 'entry_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }
}
