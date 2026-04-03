<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Role;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Member Access Repair...\n";

// 1. Identify users that should be members but aren't linked
$users = User::all();

foreach ($users as $user) {
    // If user has 'Membro (Sistema)' role but no link
    $hasRole = $user->roles()->where('name', 'Membro (Sistema)')->exists() || $user->role === 'Membro (Sistema)';
    
    if ($hasRole && !$user->member_id) {
        echo "User {$user->email} has Member role but no member_id link. Searching by email...\n";
        $member = Member::where('email', $user->email)->first();
        if ($member) {
            $user->member_id = $member->id;
            $user->role = 'Membro (Sistema)';
            $user->save();
            echo "Successfully linked user {$user->email} to member #{$member->id}\n";
        } else {
            echo "Warning: No member found for email {$user->email}\n";
        }
    }
}

echo "Repair complete.\n";
