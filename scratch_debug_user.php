<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::where('email', 'teste@membro.com')->first();
if (!$u) {
    echo "Usuario nao encontrado\n";
    exit;
}

echo "Role: " . ($u->role ?: 'NULL') . "\n";
echo "Is Super Admin: " . ($u->isSuperAdmin() ? 'YES' : 'NO') . "\n";
echo "Has view-dashboard Permission: " . ($u->hasPermission('view-dashboard') ? 'YES' : 'NO') . "\n";
echo "Permissions list: " . implode(', ', $u->all_permissions) . "\n";
