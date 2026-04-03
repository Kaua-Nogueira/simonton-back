<?php

use App\Models\User;
use App\Models\Member;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::all();
$members = Member::all();

echo "--- Users ---\n";
foreach ($users as $u) {
    echo "ID: {$u->id} | Email: '{$u->email}' | MemberID: '{$u->member_id}'\n";
}

echo "\n--- Members ---\n";
foreach ($members as $m) {
    echo "ID: {$m->id} | Email: '{$m->email}' | Name: '{$m->name}'\n";
}
