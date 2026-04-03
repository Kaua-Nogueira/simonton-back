<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::whereNotNull('member_id')->get();
foreach ($users as $u) {
    if ($u->roles()->where('name', 'Membro (Sistema)')->exists()) {
        $u->role = 'Membro (Sistema)';
        $u->save();
        echo "Updated role column for user {$u->email} to 'Membro (Sistema)'\n";
    }
}
echo "Done.\n";
