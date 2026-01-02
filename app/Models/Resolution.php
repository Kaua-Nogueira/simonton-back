<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting;
use App\Models\Member;

class Resolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id', 'topic', 'content', 'tags', 'status', 'responsible_id', 'category_id'
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function responsible()
    {
        return $this->belongsTo(Member::class, 'responsible_id');
    }
}
