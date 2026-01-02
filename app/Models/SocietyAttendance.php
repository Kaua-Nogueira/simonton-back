<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyAttendance extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'society_member_id', 'status'];

    public function activity()
    {
        return $this->belongsTo(SocietyActivity::class);
    }

    public function societyMember()
    {
        return $this->belongsTo(SocietyMember::class);
    }
}
