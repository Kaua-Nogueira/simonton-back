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
                if ($name && 
                    !str_starts_with($name, 'sanctum.') && 
                    !str_starts_with($name, 'ignition.') &&
                    !str_starts_with($name, '_ignition.')
                ) {
                    Permission::firstOrCreate(
                        ['name' => $name],
                        [
                            'description' => 'Auto generated from route', 
                            'slug' => $name
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
                    'type' => 'system', // or 'function' depending on your enum
                    'description' => 'Super Administrator with Full Access'
                ]
            );

            $allPermissions = Permission::all();
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
            $this->command->info("Admin Role synced with {$allPermissions->count()} permissions.");

            // 3. Assign Role to Specific Admin User
            $targetEmail = 'admin@admin.com';
            $user = User::where('email', $targetEmail)->first();

            if ($user) {
                // Assign ACL Role
                if (!$user->roles->contains($adminRole->id)) {
                    $user->roles()->attach($adminRole->id);
                    $this->command->info("Role 'Admin' attached to user {$targetEmail}.");
                } else {
                    $this->command->info("User {$targetEmail} already has 'Admin' role.");
                }

                // Update Legacy Column (Safety Net)
                $user->role = 'admin';
                $user->save();
                $this->command->info("Legacy 'role' column set to 'admin' for {$targetEmail}.");

            } else {
                $this->command->error("User with email {$targetEmail} not found! Please create it first.");
            }

            DB::commit();
            $this->command->info("✅ SUCCESS: Admin access fully restored.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to restore admin access: " . $e->getMessage());
        }
    }
}
