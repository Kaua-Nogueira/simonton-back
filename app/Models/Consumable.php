<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consumable extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'current_quantity', 'min_threshold'];
}
