<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['meeting_id', 'name', 'path', 'type', 'oficio_number', 'description'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
