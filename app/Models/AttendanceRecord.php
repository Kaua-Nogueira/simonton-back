<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'sunday_school_class_id',
        'date',
        'present_count',
        'visitors_count',
        'bible_count',
        'offering_amount',
        'attendees'
    ];

    protected $casts = [
        'date' => 'date',
        'attendees' => 'array',
        'offering_amount' => 'decimal:2'
    ];

    public function class()
    {
        return $this->belongsTo(SundaySchoolClass::class, 'sunday_school_class_id');
    }
}
