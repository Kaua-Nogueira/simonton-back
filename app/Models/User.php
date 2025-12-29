<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canView(): bool
    {
        return in_array($this->role, ['viewer', 'reconciler', 'approver', 'admin']);
    }

    public function canReconcile(): bool
    {
        return in_array($this->role, ['reconciler', 'approver', 'admin']);
    }

    public function canApprove(): bool
    {
        return in_array($this->role, ['approver', 'admin']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
