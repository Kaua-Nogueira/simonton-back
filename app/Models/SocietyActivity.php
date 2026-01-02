<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyActivity extends Model
{
    use HasFactory;

    protected $fillable = ['society_id', 'title', 'date', 'time', 'type', 'description'];

    protected $casts = ['date' => 'date'];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function attendances()
    {
        return $this->hasMany(SocietyAttendance::class, 'activity_id');
    }
}
