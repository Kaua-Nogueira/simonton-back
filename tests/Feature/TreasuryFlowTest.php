<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TreasuryFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $treasurer;
    private Category $category;
    private CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Setup Infrastructure
        $this->seedRolesAndPermissions();
        $this->treasurer = $this->createTreasurerUser();
        
        $this->category = Category::create(['name' => 'Dízimos', 'type' => 'income']);
        $this->costCenter = CostCenter::create(['name' => 'Geral', 'code' => '001']);
    }

    private function seedRolesAndPermissions()
    {
        // Minimal setup for ACL
        $role = Role::create(['name' => 'Treasurer', 'type' => 'system']);
        
        // Needed permissions for Transactions
        $perms = [
            'transactions.store',
            'transactions.index',
        ];

        foreach ($perms as $p) {
            $perm = Permission::create(['name' => $p, 'group' => 'treasury', 'method' => 'POST']);
            $role->permissions()->attach($perm);
        }
    }

    private function createTreasurerUser(): User
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'Treasurer')->first();
        $user->roles()->attach($role);
        return $user;
    }

    #[Test]
    public function it_can_register_an_income_transaction()
    {
        // Arrange
        $payload = [
            'type' => 'income',
            'amount' => 1500.00,
            'description' => 'Dízimo Teste',
            'date' => now()->format('Y-m-d'),
            'category_id' => $this->category->id,
            'cost_center_id' => $this->costCenter->id,
            'payment_method' => 'dinheiro',
        ];

        // Act
        $response = $this->actingAs($this->treasurer)
                         ->postJson(route('transactions.store'), $payload);

        // Assert
        $response->assertCreated();
        
        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'amount' => 1500.00,
            'description' => 'Dízimo Teste',
        ]);
    }

    #[Test]
    public function it_can_register_an_expense_transaction_and_validates_math()
    {
        // Arrange
        $expenseCategory = Category::create(['name' => 'Manutenção', 'type' => 'expense']);
        
        $payload = [
            'type' => 'expense',
            'amount' => 350.50, // Expenses are stored as positive values in payload, usually handled by backend logic
            'description' => 'Conserto Janela',
            'date' => now()->format('Y-m-d'),
            'category_id' => $expenseCategory->id,
            'cost_center_id' => $this->costCenter->id,
            'payment_method' => 'ted',
        ];

        // Act
        $response = $this->actingAs($this->treasurer)
                         ->postJson(route('transactions.store'), $payload);

        // Assert
        $response->assertCreated();

        // Check DB. Note: Depending on your Controller logic, amount might be stored negative or positive with type='expense'.
        // Assuming strictly typed storage:
        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'amount' => 350.50,
            'description' => 'Conserto Janela',
        ]);
    }

    #[Test]
    public function it_prevents_transaction_with_invalid_amount()
    {
        // Scenario C: Invalid/Negative Amount
        $payload = [
            'type' => 'income',
            'amount' => -100.00, // Should not allow negative input for amount field directly?
            'description' => 'Invalid Amount',
            'date' => now()->format('Y-m-d'),
            'category_id' => $this->category->id,
            'cost_center_id' => $this->costCenter->id,
        ];

        $response = $this->actingAs($this->treasurer)
                         ->postJson(route('transactions.store'), $payload);

        // Depending on your validation rules, this should be 422
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function it_allows_transaction_without_category_but_forces_pending()
    {
        $payload = [
            'type' => 'income',
            'amount' => 100.00,
            'description' => 'Sem Categoria',
            'date' => now()->format('Y-m-d'),
            // 'category_id' missing
            'payment_method' => 'dinheiro',
        ];

        $response = $this->actingAs($this->treasurer)
                         ->postJson(route('transactions.store'), $payload);

        $response->assertCreated();
        
        $this->assertDatabaseHas('transactions', [
            'description' => 'Sem Categoria',
            'status' => 'pending', // Key assertion
        ]);
    }
}
