<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrimonyMovement extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'from_location_id',
        'to_location_id',
        'movement_date',
        'user_id'
    ];

    protected $casts = [
        'movement_date' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(PatrimonyAsset::class, 'asset_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(PatrimonyLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(PatrimonyLocation::class, 'to_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
