<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Menu;

class ProductCampaignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'product-campaigns.index' => 'Ver lista de campanhas de produtos',
            'product-campaigns.store' => 'Criar nova campanha de produtos',
            'product-campaigns.update' => 'Editar campanha de produtos',
            'product-campaigns.destroy' => 'Excluir campanha de produtos',
            'product-campaigns.orders.index' => 'Ver e gerenciar pedidos da campanha',
            'product-campaigns.orders.update' => 'Atualizar status de pagamento/entrega de pedidos',
        ];

        $permIds = [];
        foreach ($permissions as $name => $desc) {
            $p = Permission::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $desc,
                    'description' => $desc,
                    'group' => 'Campanhas de Eventos'
                ]
            );
            $permIds[] = $p->id;
        }

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first() ?: Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching($permIds);
        }

        // Create a specific role for "Woman in Charge of Shirts"
        $ordersRole = Role::firstOrCreate(
            ['name' => 'Coordenador de Pedidos'],
            ['description' => 'Coordenador de Pedidos de Eventos', 'category' => 'Outros', 'type' => 'system']
        );
        $ordersRole->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', [
                'product-campaigns.index',
                'product-campaigns.orders.index',
                'product-campaigns.orders.update'
            ])->pluck('id')
        );

        // Add Menu Entry
        $secretaria = Menu::where('title', 'Secretaria')->first();
        if ($secretaria) {
            $menu = Menu::updateOrCreate(
                ['title' => 'Pedidos de Eventos', 'parent_id' => $secretaria->id],
                [
                    'url' => '/eventos-pedidos',
                    'icon' => 'ShoppingBag',
                    'order' => 5
                ]
            );

            $menu->permissions()->syncWithoutDetaching(
                Permission::where('name', 'product-campaigns.index')->pluck('id')
            );
        }
    }
}
