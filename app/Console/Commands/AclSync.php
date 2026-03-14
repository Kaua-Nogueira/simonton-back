<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\Permission;

class AclSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:sync {--dry-run : Only show what would be created without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize API named routes with the permissions table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ACL Synchronization...');

        $routes = Route::getRoutes()->getRoutes();
        $permissions = [];
        $newCount = 0;

        foreach ($routes as $route) {
            $name = $route->getName();
            
            // Skip unnamed routes, sanitizer routes, or plain web routes if desired
            if (!$name) continue;
            if (str_starts_with($name, 'sanctum.')) continue;
            if (str_starts_with($name, 'ignition.')) continue;
            
            // Focus on API routes usually
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/')) continue;

            $groupName = explode('.', $name)[0] ?? 'system';
            
            $permissions[] = [
                'name' => $name,
                'group' => $groupName,
            ];

            if ($this->option('dry-run')) {
                $exists = Permission::where('name', $name)->exists();
                if (!$exists) {
                    $this->line("<comment>[NEW]</comment> {$name} ({$groupName})");
                    $newCount++;
                }
            } else {
                $perm = Permission::firstOrCreate(
                    ['name' => $name],
                    ['group' => $groupName, 'description' => "Auto-generated for route {$name}"]
                );
                
                if ($perm->wasRecentlyCreated) {
                    $this->info("Created permission: {$name}");
                    $newCount++;
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run complete. {$newCount} new permissions would be created.");
        } else {
            $this->info("Sync complete. {$newCount} new permissions created.");
        }
    }
}
