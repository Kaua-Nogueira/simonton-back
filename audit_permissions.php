<?php

use App\Models\User;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::with('roles.permissions', 'permissions')->get();

foreach ($users as $u) {
    $roles = $u->roles->pluck('name')->toArray();
    $isAdmin = ($u->role === 'admin' || in_array('admin', $roles) || in_array('Admin', $roles));
    $permCount = count($u->getAllPermissions());
    
    echo "{$u->email} | Role: {$u->role} | IsAdmin: " . ($isAdmin ? "YES" : "NO") . " | Perms: {$permCount}\n";
}
