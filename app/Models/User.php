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
        'member_id',
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

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // ACL Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    // Helper Methods
    public function hasRole($role)
    {
        if (is_string($role)) {
            if ($this->role === $role) {
                return true;
            }
            return $this->roles->contains('name', $role);
        }
        return !!$role->intersect($this->roles)->count();
    }

    public function getAllPermissions()
    {
        $permissions = $this->permissions;
        
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        return $permissions->unique('id');
    }

    public function hasPermission($permission)
    {
        // Check direct permission
        if ($this->permissions->contains('name', $permission)) {
            return true;
        }
        // Check via roles
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permission)) {
                return true;
            }
        }
        // Super Admin check
        if ($this->hasRole('admin') || $this->hasRole('Admin')) {
            return true;
        }

        return false;
    }
}
