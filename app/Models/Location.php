<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'capacity', 'is_bookable'];

    protected $casts = [
        'is_bookable' => 'boolean',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
