<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyMember extends Model
{
    use HasFactory;

    protected $fillable = ['society_id', 'member_id', 'status', 'pact_date'];

    protected $casts = ['pact_date' => 'date'];

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function duesPayments()
    {
        return $this->hasMany(SocietyDuesPayment::class);
    }
}
