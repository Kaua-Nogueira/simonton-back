<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added
use App\Models\MeetingAttendance; // Added
use App\Models\Resolution; // Added
use App\Models\Document; // Added
use App\Models\Member; // Added

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'time', 'location', 'type', 'scope', 'status', 
        'opening_prayer', 'previous_minutes_reading', 'expedient', 'reports', 'closing_prayer',
        'presiding_officer_id', 'secretary_id'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function resolutions()
    {
        return $this->hasMany(Resolution::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function presidingOfficer()
    {
        return $this->belongsTo(Member::class, 'presiding_officer_id');
    }

    public function secretary()
    {
        return $this->belongsTo(Member::class, 'secretary_id');
    }
}
