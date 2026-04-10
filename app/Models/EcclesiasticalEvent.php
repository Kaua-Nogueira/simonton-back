<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcclesiasticalEvent extends BaseModel
{
    protected $fillable = [
        'title',
        'type',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'location',
        'ministry',
        'audience',
        'status',
        'is_recurring',
        'recurrence_rule',
        'parent_event_id',
        'ebd_class_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'is_recurring' => 'boolean',
        'recurrence_rule' => 'array',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EventAssignment::class, 'event_id');
    }

    public function parentEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_event_id');
    }

    public function recurringChildren(): HasMany
    {
        return $this->hasMany(self::class, 'parent_event_id');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(EventChangeLog::class, 'event_id');
    }

    public function ebdClass(): BelongsTo
    {
        return $this->belongsTo(SundaySchoolClass::class, 'ebd_class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
