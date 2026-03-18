<?php
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

$user = User::first();
if ($user) {
    echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
    echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "SuperAdmin: " . ($user->isSuperAdmin() ? 'Yes' : 'No') . "\n";
    
    // Check for treasury permissions
    $treasuryPerms = ["treasury.entries.index", "entries.index"];
    foreach ($treasuryPerms as $p) {
        echo "Has $p: " . ($user->hasPermission($p) ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "No users found.\n";
}
