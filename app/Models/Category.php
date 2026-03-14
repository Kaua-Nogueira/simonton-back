<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'parent_id',
        'code',
        'is_active'
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->code)) {
                $category->code = static::generateNextCode($category->parent_id, $category->type);
            }
        });
    }

    public static function generateNextCode($parentId, $type)
    {
        if (!$parentId) {
            $maxCode = static::whereNull('parent_id')
                ->where('type', $type)
                ->where('code', 'not like', '%.%')
                ->max('code');
            
            if ($maxCode && is_numeric($maxCode)) {
                return (string)(intval($maxCode) + 1);
            }
            return $type === 'income' ? '1' : '2';
        }

        $parent = static::find($parentId);
        if (!$parent || empty($parent->code)) return null;

        $maxChildCode = static::where('parent_id', $parentId)->max('code');

        if ($maxChildCode) {
            $parts = explode('.', $maxChildCode);
            $lastPart = array_pop($parts);
            $nextPart = intval($lastPart) + 1;
            return $parent->code . '.' . $nextPart;
        }

        return $parent->code . '.1';
    }

    protected $casts = [
        'is_active' => 'boolean',
        'parent_id' => 'integer',
        // Keeping potentially existing casts if they were relevant, though columns might not exist
        'is_taxable' => 'boolean',
        'is_restricted' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren')->orderBy('code');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
