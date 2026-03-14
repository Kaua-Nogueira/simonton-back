<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Permission;

class FinancialMenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create permissions if they don't exist
        $permissions = [
            ['name' => 'finance.categories.view', 'description' => 'Visualizar Plano de Contas'],
            ['name' => 'finance.categories.create', 'description' => 'Criar Categoria Financeira'],
            ['name' => 'finance.categories.edit', 'description' => 'Editar Categoria Financeira'],
            ['name' => 'finance.categories.delete', 'description' => 'Deletar Categoria Financeira'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name']], ['description' => $p['description'], 'group' => 'finance']);
        }

        // 2. Create "Financeiro" Menu
        $financeiro = Menu::firstOrCreate(
            ['title' => 'Financeiro'],
            [
                'url' => '#',
                'icon' => 'DollarSign',
                'order' => 50,
            ]
        );

        // 3. Create "Plano de Contas" Sub-menu
        $plano = Menu::firstOrCreate(
            ['title' => 'Plano de Contas', 'parent_id' => $financeiro->id],
            [
                'url' => '/financeiro/plano-de-contas',
                'icon' => 'ListTree',
                'order' => 1,
            ]
        );

        // 4. Assign permissions to menu
        $viewPerm = Permission::where('name', 'finance.categories.view')->first();
        if ($viewPerm) {
            $plano->permissions()->syncWithoutDetaching([$viewPerm->id]);
        }

        // 5. Give admin user the permissions
        $admin = \App\Models\User::where('email', 'admin@admin')->first();
        if ($admin) {
            $admin->givePermissionTo('finance.categories.view');
        }
    }
}
