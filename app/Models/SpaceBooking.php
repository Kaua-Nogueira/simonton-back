<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpaceBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'requester_id',
        'event_name',
        'start_time',
        'end_time',
        'status',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function requester()
    {
        return $this->belongsTo(Member::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
