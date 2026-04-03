<?php

use App\Models\User;
use App\Models\Member;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::whereNotNull('member_id')->first();
if (!$user) {
    echo "No user with member_id found.\n";
} else {
    echo "User found: " . $user->email . " with member_id=" . $user->member_id . "\n";
    $member = $user->member;
    if ($member) {
        echo "Member found: " . $member->name . "\n";
    } else {
        echo "ERROR: Member relationship returned NULL even with member_id present!\n";
    }
}
