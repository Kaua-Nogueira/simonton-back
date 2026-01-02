<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SundaySchoolClass extends Model
{
    protected $fillable = ['name', 'target_audience', 'location'];

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function members()
    {
        return $this->belongsToMany(Member::class, 'class_enrollments')
                    ->withPivot('role', 'year')
                    ->withTimestamps();
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
