<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class RestoreAdminAccessSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        if (!$user) {
            $this->command->error("No user found.");
            return;
        }

        $role = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['type' => 'system', 'description' => 'Super Admin - Full Access']
        );

        // Sync all permissions to this role
        $permissions = Permission::all();
        $role->permissions()->sync($permissions);

        // Assign role to user
        $user->roles()->syncWithoutDetaching([$role->id]);
        
        // Update legacy column if exists
        $user->role = 'admin';
        $user->save();

        $this->command->info("Admin access restored for user: {$user->email}");
    }
}
