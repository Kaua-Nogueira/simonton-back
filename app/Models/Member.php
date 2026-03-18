<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends BaseModel
{
    use HasFactory, \App\Traits\Auditable;

    protected $fillable = [
        'name',
        'cpf',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'status',
        'birth_date',
        'roll_number',
        'admission_date',
        'admission_type',
        'previous_church',
        'dismissal_date',
        'dismissal_type',
        'destination_church',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'admission_date' => 'date',
        'dismissal_date' => 'date',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'member_role')
            ->withPivot('id', 'start_date', 'end_date')
            ->orderByPivot('start_date', 'desc');
    }

    public function getFormattedCpfAttribute(): string
    {
        $cpf = $this->cpf;
        if (!$cpf) return '';
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
