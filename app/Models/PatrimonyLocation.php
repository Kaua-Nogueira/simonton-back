<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrimonyLocation extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function assets(): HasMany
    {
        return $this->hasMany(PatrimonyAsset::class, 'location_id');
    }
}
