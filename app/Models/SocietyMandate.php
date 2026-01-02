<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyMandate extends Model
{
    use HasFactory;

    protected $fillable = ['society_id', 'year', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date', 
        'end_date' => 'date'
    ];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function roles()
    {
        return $this->hasMany(MandateRole::class, 'mandate_id');
    }
}
