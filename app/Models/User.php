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
    
    /**
     * Check if user has a specific role (by name).
     * Now strictly uses the relationship, ignoring the legacy 'role' string column.
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        return !!$role->intersect($this->roles)->count();
    }

    /**
     * Check if user is a Super Admin.
     * Checks for 'Admin' role case-insensitively.
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles->contains(function ($role) {
            return strtolower($role->name) === 'admin';
        });
    }

    protected $permissionCache = null;

    public function getAllPermissions()
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $permissions = $this->permissions;
        
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        $this->permissionCache = $permissions->unique('id');
        return $this->permissionCache;
    }

    public function hasPermission($permission)
    {
        // Super Admin check
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getAllPermissions()->contains('name', $permission);
    }

    public function givePermissionTo($permission)
    {
        $p = Permission::where('name', $permission)->first();
        if ($p) {
            $this->permissions()->syncWithoutDetaching([$p->id]);
        }
    }
}
