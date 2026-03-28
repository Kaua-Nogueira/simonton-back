<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrimonyLocation extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'prefix', 'last_counter', 'capacity', 'is_bookable'];

    public function assets(): HasMany
    {
        return $this->hasMany(PatrimonyAsset::class, 'location_id');
    }
}
