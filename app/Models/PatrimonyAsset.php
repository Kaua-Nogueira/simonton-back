<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrimonyAsset extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tombo',
        'name',
        'description',
        'category_id',
        'location_id',
        'state',
        'acquisition_value',
        'estimated_value',
        'acquisition_date',
        'responsible',
        'observations',
        'is_active',
        'user_id',
        'disposal_reason',
        'disposal_date',
        'disposal_observations',
    ];

    protected $casts = [
        'acquisition_value' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'acquisition_date' => 'date',
        'disposal_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PatrimonyCategory::class, 'category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PatrimonyLocation::class, 'location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PatrimonyMovement::class, 'asset_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(AssetLoan::class, 'asset_id');
    }

    public function activeLoan()
    {
        return $this->hasOne(AssetLoan::class, 'asset_id')->where('status', 'active');
    }
}
