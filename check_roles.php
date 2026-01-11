<?php

use App\Models\Role;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = Role::withCount('permissions')->get();

foreach ($roles as $r) {
    echo "Role: {$r->name} | Permissions: {$r->permissions_count}\n";
}
