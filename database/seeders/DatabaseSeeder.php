<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);

        // Seed Categories
        $categories = [
            ['name' => 'Salário', 'type' => 'income'],
            ['name' => 'Freelance', 'type' => 'income'],
            ['name' => 'Investimentos', 'type' => 'income'],
            ['name' => 'Alimentação', 'type' => 'expense'],
            ['name' => 'Transporte', 'type' => 'expense'],
            ['name' => 'Moradia', 'type' => 'expense'],
            ['name' => 'Saúde', 'type' => 'expense'],
            ['name' => 'Educação', 'type' => 'expense'],
            ['name' => 'Lazer', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Seed Cost Centers
        $costCenters = [
            ['name' => 'Administrativo', 'description' => 'Despesas administrativas'],
            ['name' => 'Operacional', 'description' => 'Despesas operacionais'],
            ['name' => 'Marketing', 'description' => 'Despesas de marketing'],
            ['name' => 'Vendas', 'description' => 'Despesas de vendas'],
        ];

        foreach ($costCenters as $costCenter) {
            CostCenter::create($costCenter);
        }

        // Seed Members
        Member::create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '(11) 98765-4321',
            'cpf' => '123.456.789-00',
            'status' => 'active',
        ]);

        // Seed Roles (Ofícios/Funções)
        $roles = [
            // Eclesiásticos (Ofícios)
            ['name' => 'Pastor', 'type' => 'ecclesiastical', 'description' => 'Pastor Titular'],
            ['name' => 'Presbítero', 'type' => 'ecclesiastical', 'description' => 'Presbítero'],
            ['name' => 'Diácono', 'type' => 'ecclesiastical', 'description' => 'Diácono'],
            ['name' => 'Missionário', 'type' => 'ecclesiastical', 'description' => 'Missionário'],
            
            // Administrativos (Funções)
            ['name' => 'Tesoureiro', 'type' => 'administrative', 'description' => 'Responsável pelas finanças'],
            ['name' => 'Secretário', 'type' => 'administrative', 'description' => 'Responsável pela secretaria'],
            ['name' => 'Zelador', 'type' => 'administrative', 'description' => 'Responsável pela limpeza'],
            
            // Liderança (Ministérios)
            ['name' => 'Líder de Louvor', 'type' => 'leadership', 'description' => 'Líder do Ministério de Louvor'],
            ['name' => 'Líder de Jovens', 'type' => 'leadership', 'description' => 'Líder do Ministério de Jovens'],
            ['name' => 'Líder de Crianças', 'type' => 'leadership', 'description' => 'Líder do Ministério Infantil'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }

        $this->call(EbdSeeder::class);
    }
}
