<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAssignment extends BaseModel
{
    protected $fillable = [
        'event_id',
        'member_id',
        'service_area',
        'role_name',
        'status',
        'notes',
        'responded_at',
        'replaced_by_member_id',
        'created_by',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalEvent::class, 'event_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function replacedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'replaced_by_member_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
