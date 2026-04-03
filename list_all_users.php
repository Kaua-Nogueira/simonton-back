<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::all();
foreach ($users as $u) {
    echo "ID: {$u->id} | Email: {$u->email} | Role (Col): {$u->getRawOriginal('role')} | Role (Getter): {$u->role} | MemberID: {$u->member_id}\n";
}
echo "Total users: " . $users->count() . "\n";
