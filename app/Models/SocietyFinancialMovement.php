<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyFinancialMovement extends Model
{
    use HasFactory;

    protected $fillable = ['society_id', 'description', 'amount', 'type', 'date', 'category'];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }
}
