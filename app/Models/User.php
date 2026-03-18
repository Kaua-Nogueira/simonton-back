<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, \App\Traits\Auditable;

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

    protected $appends = ['is_super_admin', 'all_permissions'];

    public function canView(): bool
    {
        return $this->hasPermission('view-dashboard') || $this->isSuperAdmin();
    }

    public function canReconcile(): bool
    {
        return $this->hasPermission('transactions.index') || $this->isSuperAdmin();
    }

    public function canApprove(): bool
    {
        return $this->hasPermission('transactions.confirm') || $this->isSuperAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->isSuperAdmin();
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
     */
    public function hasRole($role)
    {
        if ($this->isSuperAdmin()) return true;

        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        if (is_string($role)) {
            return $roles->contains('name', $role);
        }
        return !!$role->intersect($roles)->count();
    }

    /**
     * Check if user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        // Use relationLoaded to avoid triggering lazy loading exception if preventLazyLoading is on
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        return $roles->contains(function ($role) {
            return strtolower($role->name) === 'admin';
        });
    }

    public function getAllPermissionsAttribute()
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    protected $permissionCache = null;

    public function getAllPermissions()
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        // Get direct permissions
        $permissions = $this->permissions()->get();
        
        // Get permissions from roles
        $userRoles = $this->roles()->with('permissions')->get();
        foreach ($userRoles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        $this->permissionCache = $permissions->unique('id');
        return $this->permissionCache;
    }

    public function hasPermission($permission)
    {
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
