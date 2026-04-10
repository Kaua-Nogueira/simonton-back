<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends BaseModel
{
    protected $fillable = [
        'sunday_school_class_id',
        'date',
        'present_count',
        'visitors_count',
        'bible_count',
        'magazine_count',
        'teacher_id',
        'attendees'
    ];

    protected $casts = [
        'date' => 'date',
        'attendees' => 'array',
    ];

    public function teacher()
    {
        return $this->belongsTo(Member::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(SundaySchoolClass::class, 'sunday_school_class_id');
    }
}
