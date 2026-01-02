<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocietyDuesPayment extends Model
{
    use HasFactory;

    protected $fillable = ['society_member_id', 'year', 'month', 'amount', 'payment_date'];

    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2'];

    public function societyMember()
    {
        return $this->belongsTo(SocietyMember::class);
    }
}
