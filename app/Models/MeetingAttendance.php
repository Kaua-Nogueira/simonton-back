<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = ['meeting_id', 'member_id', 'status', 'justification'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
