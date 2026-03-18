<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyObligation extends Model
{
    use HasFactory;

    protected $fillable = [
        'society_id', 
        'description', 
        'due_date', 
        'amount', 
        'status', 
        'movement_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function movement()
    {
        return $this->belongsTo(SocietyFinancialMovement::class, 'movement_id');
    }
}
