<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Society extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'abbreviation', 'min_age', 'max_age', 'gender_restriction', 'description'];

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
}
