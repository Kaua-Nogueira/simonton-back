<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Permission;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // Clear existing menus to avoid duplicates? Or updateOrCreate.
        // Let's use updateOrCreate based on title/url.
        // Or truncate? Truncate is cleaner for a "Reset" request.
        // Menu::truncate(); // Be careful with foreign keys.

        $menus = [
            [
                'title' => 'Dashboard',
                'url' => '/',
                'icon' => 'LayoutDashboard',
                'permission' => null, // Public or requires login
                'order' => 1
            ],
            [
                'title' => 'Financeiro',
                'url' => '#',
                'icon' => 'DollarSign',
                'permission' => null, 
                'order' => 2,
                'children' => [
                    ['title' => 'Entradas', 'url' => '/entradas', 'icon' => 'TrendingUp', 'permission' => 'transactions.index'],
                    ['title' => 'Saídas', 'url' => '/saidas', 'icon' => 'TrendingDown', 'permission' => 'transactions.index'],
                    ['title' => 'Registro de Caixa', 'url' => '/registro-caixa', 'icon' => 'Receipt', 'permission' => 'cash-register.index'],
                    ['title' => 'Orçamento', 'url' => '/financeiro/orcamento', 'icon' => 'TrendingUp', 'permission' => 'finance.budgets.index'],
                    // 'Obrigações' route doesn't exist yet, skipping or adding generic
                    ['title' => 'Obrigações', 'url' => '/financeiro/obrigacoes', 'icon' => 'CheckSquare', 'permission' => 'finance.budgets.index'], 
                ]
            ],
            [
                'title' => 'Membros',
                'url' => '/membros',
                'icon' => 'Users',
                'permission' => 'members.index',
                'order' => 3
            ],
            [
                'title' => 'Relatórios',
                'url' => '/relatorios',
                'icon' => 'BarChart3',
                'permission' => 'reports.view',
                'order' => 4
            ],
            [
                'title' => 'Escola Dominical',
                'url' => '#',
                'icon' => 'BookOpen',
                'permission' => null,
                'order' => 5,
                'children' => [
                    ['title' => 'Dashboard', 'url' => '/ebd', 'icon' => 'LayoutDashboard', 'permission' => 'ebd.classes.index'],
                    ['title' => 'Classes', 'url' => '/ebd/classes', 'icon' => 'Users', 'permission' => 'ebd.classes.index'],
                ]
            ],
            [
                'title' => 'Secretaria',
                'url' => '#',
                'icon' => 'ScrollText',
                'permission' => null,
                'order' => 6,
                'children' => [
                    ['title' => 'Atas', 'url' => '/secretaria/atas', 'icon' => 'ScrollText', 'permission' => 'meetings.index'],
                    ['title' => 'Resoluções', 'url' => '/secretaria/resolucoes', 'icon' => 'CheckSquare', 'permission' => 'resolutions.index'],
                ]
            ],
            [
                'title' => 'Sociedades Internas',
                'url' => '/sociedades',
                'icon' => 'Users',
                'permission' => 'societies.index',
                'order' => 7
            ],
            [
                'title' => 'Patrimônio',
                'url' => '/patrimonio',
                'icon' => 'Archive',
                'permission' => 'patrimony.assets.index',
                'order' => 8
            ],
             [
                'title' => 'Cadastros',
                'url' => '/cadastros',
                'icon' => 'Settings',
                'permission' => 'categories.index',
                'order' => 9
            ],
            [
                'title' => 'Configurações',
                'url' => '/configuracoes',
                'icon' => 'Settings',
                'permission' => 'settings.index', // Route might not exist, but let's keep
                'order' => 10
            ],
            [
                'title' => 'Controle de Acesso',
                'url' => '#',
                'icon' => 'Shield',
                'permission' => 'acl.roles.index', // Need minimal acl perm
                'order' => 11,
                'children' => [
                    ['title' => 'Usuários', 'url' => '/admin/acl/users', 'icon' => 'Users', 'permission' => 'acl.users.index'],
                    ['title' => 'Papéis', 'url' => '/admin/acl/roles', 'icon' => 'CheckSquare', 'permission' => 'acl.roles.index'],
                    ['title' => 'Permissões', 'url' => '/admin/acl/permissions', 'icon' => 'BookOpen', 'permission' => 'acl.permissions.index'],
                    ['title' => 'Menus', 'url' => '/admin/acl/menus', 'icon' => 'LayoutDashboard', 'permission' => 'acl.menus.index'],
                ]
            ]
        ];

        foreach ($menus as $m) {
            $menu = Menu::firstOrCreate(
                ['title' => $m['title'], 'url' => $m['url']],
                ['icon' => $m['icon'], 'order' => $m['order']]
            );

            // Attach permission if exists
            if (!empty($m['permission'])) {
                $perm = Permission::where('name', $m['permission'])->first();
                if ($perm) {
                    $menu->permissions()->syncWithoutDetaching([$perm->id]);
                }
            }

            if (isset($m['children'])) {
                foreach ($m['children'] as $idx => $child) {
                     $childMenu = Menu::firstOrCreate(
                        ['title' => $child['title'], 'url' => $child['url'], 'parent_id' => $menu->id],
                        ['icon' => $child['icon'], 'order' => $idx + 1]
                    );

                    if (!empty($child['permission'])) {
                        $perm = Permission::where('name', $child['permission'])->first();
                        if ($perm) {
                            $childMenu->permissions()->syncWithoutDetaching([$perm->id]);
                        }
                    }
                }
            }
        }
        
        $this->command->info('Menus populated successfully.');
    }
}
