<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class RestoreAdminAccessSeeder extends Seeder
{
    public function run()
    {
        // 1. Scan and Create Permissions from Routes
        $this->command->info('Scanning routes to generate permissions...');
        $routes = Route::getRoutes();
        $permissionsCount = 0;

        DB::beginTransaction();
        try {
            foreach ($routes as $route) {
                $name = $route->getName();

                // Filter relevant API routes
                if (
                    $name &&
                    !str_starts_with($name, 'sanctum.') &&
                    !str_starts_with($name, 'ignition.') &&
                    !str_starts_with($name, '_ignition.')
                ) {
                    Permission::firstOrCreate(
                        ['name' => $name],
                        [
                            'group' => explode('.', $name)[0] ?? 'system',
                            'description' => 'Auto generated from route'
                        ]
                    );
                    $permissionsCount++;
                }
            }
            $this->command->info("Permissions checked/created: {$permissionsCount}");

            // 2. Ensure Admin Role Exists and has ALL permissions
            $this->command->info('Configuring Admin Role...');
            $adminRole = Role::firstOrCreate(
                ['name' => 'Admin'],
                [
                    'type' => 'system',
                    'description' => 'Super Administrator with Full Access'
                ]
            );

            $allPermissions = Permission::all();
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
            $this->command->info("Admin Role synced with {$allPermissions->count()} permissions.");

            // 3. Assign Role to Admin Users
            $this->command->info('Assigning Admin role to authorized users...');
            
            // Handle specific admin emails and anyone with legacy 'admin' role
            $adminEmails = ['admin@admin.com', 'admin@admin'];
            $adminUsers = User::whereIn('email', $adminEmails)
                ->orWhere('role', 'admin')
                ->get();

            if ($adminUsers->count() > 0) {
                foreach ($adminUsers as $user) {
                    if (!$user->roles->contains($adminRole->id)) {
                        $user->roles()->attach($adminRole->id);
                        $this->command->info("Role 'Admin' attached to user {$user->email}.");
                    }
                    
                    // Legacy sync
                    $user->role = 'admin';
                    $user->save();
                }
            } else {
                $this->command->error("No admin users found to restore access!");
            }

            DB::commit();
            $this->command->info("✅ SUCCESS: Admin access fully restored.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to restore admin access: " . $e->getMessage());
        }
    }
}
