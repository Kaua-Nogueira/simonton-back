<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PatrimonyAsset;

class AssetLoan extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'requester_name',
        'member_id',
        'checkout_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'checkout_date' => 'datetime',
        'expected_return_date' => 'datetime',
        'actual_return_date' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(PatrimonyAsset::class, 'asset_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
