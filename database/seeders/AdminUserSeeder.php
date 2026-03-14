<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Admin Role exists
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrador Geral', 'type' => 'system']
        );

        // 2. Assign ALL permissions to Admin Role
        $permissions = Permission::all();
        $adminRole->permissions()->sync($permissions);

        // 3. Create or Update Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@admin'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin'), // Password as 'admin' for simplicity as requested
                'role' => 'admin',
            ]
        );

        // 4. Assign Role to User
        if (!$user->roles->contains($adminRole->id)) {
            $user->roles()->attach($adminRole);
        }

        $this->command->info("Admin user created/updated: admin@admin / admin");
    }
}
