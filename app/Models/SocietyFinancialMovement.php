<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyFinancialMovement extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'society_id', 'description', 'amount', 'type', 'date', 'category', 
        'attachment_path', 'is_confirmed'
    ];

    protected $casts = [
        'date' => 'date', 
        'amount' => 'decimal:2',
        'is_confirmed' => 'boolean'
    ];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }
}
