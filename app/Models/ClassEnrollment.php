<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassEnrollment extends Model
{
    protected $fillable = ['sunday_school_class_id', 'member_id', 'role', 'year'];

    public function class()
    {
        return $this->belongsTo(SundaySchoolClass::class, 'sunday_school_class_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
