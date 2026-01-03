<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreventiveSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'asset_id',
        'location_id',
        'frequency_days',
        'last_performed_at',
        'next_due_date',
    ];

    protected $casts = [
        'last_performed_at' => 'date',
        'next_due_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
