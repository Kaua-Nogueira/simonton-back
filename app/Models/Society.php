<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Society extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'abbreviation', 'min_age', 'max_age', 'gender_restriction', 'description', 'logo_path', 'is_system'];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) return null;
        if (str_starts_with($this->logo_path, 'http')) return $this->logo_path;
        return asset('storage/' . $this->logo_path);
    }

    public function members()
    {
        return $this->hasMany(SocietyMember::class);
    }

    public function mandates()
    {
        return $this->hasMany(SocietyMandate::class);
    }

    public function activities()
    {
        return $this->hasMany(SocietyActivity::class);
    }

    public function financialMovements()
    {
        return $this->hasMany(SocietyFinancialMovement::class);
    }

    public function obligations()
    {
        return $this->hasMany(SocietyObligation::class);
    }
}
