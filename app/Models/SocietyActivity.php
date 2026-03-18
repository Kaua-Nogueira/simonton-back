<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'society_id', 'title', 'date', 'time', 'type', 'description', 
        'estimated_cost', 'estimated_revenue'
    ];

    protected $casts = [
        'date' => 'date',
        'estimated_cost' => 'decimal:2',
        'estimated_revenue' => 'decimal:2'
    ];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function attendances()
    {
        return $this->hasMany(SocietyAttendance::class, 'activity_id');
    }
}
