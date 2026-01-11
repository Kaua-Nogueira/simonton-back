<?php

use App\Models\Role;
use App\Models\Permission;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = Role::where('name', 'Tesoureiro')->first();
if (!$role) exit("Role not found");

// Restore menus.index so the sidebar can render
$permsToAdd = ['menus.index', 'roles.index']; // Adding roles.index just in case for dropdowns
$ids = Permission::whereIn('name', $permsToAdd)->pluck('id');

$role->permissions()->syncWithoutDetaching($ids);

echo "Restored menus.index and roles.index to Tesoureiro.\n";
