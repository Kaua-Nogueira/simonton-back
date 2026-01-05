<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser($roleName = 'viewer') {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => $roleName // Legacy column support
        ]);

        if ($roleName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['type' => 'system', 'description' => 'Test Role']
            );
            $user->roles()->attach($role);
        }

        return $user;
    }

    private function createTestTransaction() {
        return Transaction::create([
            'amount' => 100.00,
            'date' => now(),
            'description' => 'Test Transaction',
            'type' => 'expense',
            'status' => 'pending',
        ]);
    }

    public function test_it_blocks_access_to_unnamed_protected_routes()
    {
        Route::middleware(['auth:sanctum', 'acl'])
            ->get('/api/test-unnamed', function () {
                return 'should not be reachable';
            });

        $user = $this->createTestUser();

        $response = $this->actingAs($user)->getJson('/api/test-unnamed');

        // Middleware logs error and returns 403
        $response->assertStatus(403);
    }

    public function test_it_denies_access_to_transaction_without_permission()
    {
        $user = $this->createTestUser('viewer');
        $transaction = $this->createTestTransaction();

        $response = $this->actingAs($user)->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    public function test_it_allows_access_to_transaction_with_permission()
    {
        $user = $this->createTestUser();
        
        $role = Role::create(['name' => 'Treasurer', 'type' => 'system']);
        $permission = Permission::create([
            'name' => 'transactions.show', 
            'group' => 'transactions',
            'description' => 'View Transaction',
            'method' => 'GET'
        ]);
        
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
        
        // Refresh to ensure roles and permissions are loaded
        $user->refresh();
        $user->load('roles.permissions');

        $transaction = $this->createTestTransaction();

        $response = $this->actingAs($user)->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);
    }

    public function test_admin_bypasses_all_policy_and_acl_checks()
    {
        $admin = $this->createTestUser('admin');
        $transaction = $this->createTestTransaction();
        
        // Ensure roles loaded
        $admin->load('roles');

        $response = $this->actingAs($admin)->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);
    }
}
