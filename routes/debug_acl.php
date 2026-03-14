<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-permissions', function () {
    $user = \App\Models\User::with('permissions', 'roles.permissions')->where('email', 'admin@admin.com')->first();
    return response()->json([
        'user' => $user->name,
        'role' => $user->role,
        'direct_permissions' => $user->permissions->pluck('name'),
        'role_permissions' => $user->roles->flatMap->permissions->pluck('name'),
        'all_permissions' => $user->getAllPermissions()->pluck('name'),
        'menus' => \App\Models\Menu::with('permissions')->get()
    ]);
});
