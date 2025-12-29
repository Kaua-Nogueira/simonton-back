<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Member;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
