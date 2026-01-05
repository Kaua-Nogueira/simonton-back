<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScanPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan all system routes and update permissions table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $count = 0;

        foreach ($routes as $route) {
            $name = $route->getName();
            
            // Ignore routes without name or specific system routes
            if (!$name || str_starts_with($name, 'sanctum.') || str_starts_with($name, 'ignition.')) {
                continue;
            }

            // Extract group (e.g. 'users' from 'users.index')
            $group = explode('.', $name)[0];

            // Extract method (get, post, put, delete)
            $methods = $route->methods();
            // Filter out HEAD if others exist
            $methods = array_filter($methods, fn($m) => $m !== 'HEAD');
            $method = reset($methods) ?: 'GET';

            \App\Models\Permission::updateOrCreate(
                ['name' => $name],
                [
                    'group' => $group,
                    'method' => $method,
                    'description' => 'Automatically generated permission for ' . $name
                ]
            );
            $count++;
        }

        $this->info("Scanned and updated {$count} permissions.");
    }
}
