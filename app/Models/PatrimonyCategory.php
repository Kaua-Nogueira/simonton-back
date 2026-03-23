<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrimonyCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'prefix', 'last_counter'];

    public function assets(): HasMany
    {
        return $this->hasMany(PatrimonyAsset::class, 'category_id');
    }
}
