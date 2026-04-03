<?php

use App\Models\Role;
use App\Models\Permission;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$permissions = [
    'members.portal.me' => 'Visualizar próprio perfil (Portal)',
    'members.portal.contributions' => 'Visualizar próprias contribuições (Portal)',
];

foreach ($permissions as $name => $description) {
    Permission::firstOrCreate(['name' => $name], ['description' => $description]);
}

$role = Role::where('name', 'Membro (Sistema)')->first();
if ($role) {
    $roleIds = Permission::whereIn('name', array_keys($permissions))->pluck('id');
    $role->permissions()->syncWithoutDetaching($roleIds);
    echo "Permissions sync'd for Membro (Sistema)\n";
} else {
    echo "Role Membro (Sistema) not found\n";
}
