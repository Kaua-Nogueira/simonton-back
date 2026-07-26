<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MandateRole extends BaseModel
{
    use HasFactory;

    protected $fillable = ['mandate_id', 'member_id', 'role_name', 'role_type'];

    public function mandate()
    {
        return $this->belongsTo(SocietyMandate::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    protected static function booted()
    {
        static::saved(function ($mandateRole) {
            User::syncSocietyLeaderRoleForMember($mandateRole->member_id);
        });

        static::deleted(function ($mandateRole) {
            User::syncSocietyLeaderRoleForMember($mandateRole->member_id);
        });
    }
}
