<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Support\Facades\Artisan;

class AclSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Scan Permissions
        Artisan::call('acl:scan');

        // 2. Create System Roles
        Role::firstOrCreate(['name' => 'Admin'], [
            'type' => 'system',
            'description' => 'Acesso total ao sistema'
        ]);

        $treasurer = Role::firstOrCreate(['name' => 'Tesoureiro (Sistema)'], [
            'type' => 'system',
            'description' => 'Gestão financeira e diaconia'
        ]);

        $secretary = Role::firstOrCreate(['name' => 'Secretário (Sistema)'], [
            'type' => 'system',
            'description' => 'Gestão de membros e atas'
        ]);

        // 3. Assign Permissions
        $treasurerPerms = Permission::where('group', 'LIKE', '%Financeiro%')
            ->orWhere('group', 'LIKE', '%Tesouraria%')
            ->pluck('id');
        $treasurer->permissions()->sync($treasurerPerms);

        $secretaryPerms = Permission::where('group', 'LIKE', '%Secretaria%')
            ->orWhere('group', 'LIKE', '%Educação%')
            ->orWhere('group', 'LIKE', '%Membr%')
            ->pluck('id');
        $secretary->permissions()->sync($secretaryPerms);

        // 4. Create Menus
        Menu::truncate();

        // Dashboard
        Menu::create([
            'title' => 'Dashboard',
            'url' => '/',
            'icon' => 'LayoutDashboard',
            'order' => 1
        ]);

        // Secretaria
        $secretaria = Menu::create([
            'title' => 'Secretaria',
            'url' => '#',
            'icon' => 'BookOpen',
            'order' => 2
        ]);
        
        $secretaria->children()->createMany([
            ['title' => 'Membros', 'url' => '/membros', 'icon' => 'Users', 'order' => 1],
            ['title' => 'Atas e Reuniões', 'url' => '/secretaria/atas', 'icon' => 'ScrollText', 'order' => 2],
            ['title' => 'Resoluções', 'url' => '/secretaria/resolucoes', 'icon' => 'CheckSquare', 'order' => 3],
        ]);

        // Financeiro
        $financeiro = Menu::create([
            'title' => 'Financeiro',
            'url' => '#',
            'icon' => 'DollarSign',
            'order' => 3
        ]);

        $financeiro->children()->createMany([
            ['title' => 'Entradas', 'url' => '/entradas', 'icon' => 'TrendingUp', 'order' => 1],
            ['title' => 'Saídas', 'url' => '/saidas', 'icon' => 'TrendingDown', 'order' => 2],
            ['title' => 'Registro de Caixa', 'url' => '/registro-caixa', 'icon' => 'Wallet', 'order' => 3],
            ['title' => 'Plano de Contas', 'url' => '/financeiro/plano-de-contas', 'icon' => 'ListTree', 'order' => 4],
            ['title' => 'Orçamentos', 'url' => '/financeiro/orcamento', 'icon' => 'BarChart3', 'order' => 5],
            ['title' => 'Obrigações', 'url' => '/financeiro/obrigacoes', 'icon' => 'Calendar', 'order' => 6],
        ]);

        // Diaconia
        $diaconia = Menu::create([
            'title' => 'Diaconia',
            'url' => '#',
            'icon' => 'Receipt',
            'order' => 4
        ]);

        $diaconia->children()->createMany([
            ['title' => 'Nova Conferência', 'url' => '/diaconia/conferencia/nova', 'icon' => 'Plus', 'order' => 1],
            ['title' => 'Histórico', 'url' => '/diaconia', 'icon' => 'ScrollText', 'order' => 2],
        ]);

        // Other modules
        Menu::create([
            'title' => 'Tesouraria',
            'url' => '/tesouraria',
            'icon' => 'ShieldCheck',
            'order' => 5
        ]);

        Menu::create([
            'title' => 'Patrimônio',
            'url' => '/patrimonio',
            'icon' => 'Archive',
            'order' => 6
        ]);

        Menu::create([
            'title' => 'Educação Cristã',
            'url' => '/ebd',
            'icon' => 'BookOpen',
            'order' => 7
        ]);

        // Sociedades
        $sociedades = Menu::create([
            'title' => 'Sociedades',
            'url' => '#',
            'icon' => 'Users',
            'order' => 8
        ]);

        // Basic menu items
        $sociedades->children()->createMany([
            ['title' => 'Relatório Geral', 'url' => '/sociedades/relatorio', 'icon' => 'BarChart3', 'order' => 1],
        ]);

        // Individual Society Menus (President/Specific Roles)
        try {
            $allSocieties = \App\Models\Society::all();
            foreach ($allSocieties as $index => $society) {
                $sociedades->children()->create([
                    'title' => $society->abbreviation,
                    'url' => '/sociedades/' . $society->id,
                    'icon' => 'ChevronRight',
                    'order' => $index + 2
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist yet or other issues
        }

        Menu::create([
            'title' => 'Cadastros',
            'url' => '/cadastros',
            'icon' => 'PlusCircle',
            'order' => 9
        ]);

        // Relatórios
        $relatorios = Menu::create([
            'title' => 'Relatórios',
            'url' => '#',
            'icon' => 'BarChart3',
            'order' => 10
        ]);

        $relatorios->children()->createMany([
            ['title' => 'Visão Geral', 'url' => '/relatorios', 'icon' => 'PieChart', 'order' => 1],
            ['title' => 'Dízimos e Ofertas', 'url' => '/financeiro/relatorios/dizimos', 'icon' => 'Heart', 'order' => 2],
            ['title' => 'Relatório Mensal', 'url' => '/relatorios/mensal', 'icon' => 'Calendar', 'order' => 3],
        ]);

        // Admin
        $adminMenu = Menu::create([
            'title' => 'Administração',
            'url' => '#',
            'icon' => 'Settings',
            'order' => 11
        ]);

        $adminMenu->children()->createMany([
            ['title' => 'Controle de Acesso', 'url' => '/admin/acl/roles', 'icon' => 'Lock', 'order' => 1],
            ['title' => 'Logs de Auditoria', 'url' => '/admin/acl/logs', 'icon' => 'History', 'order' => 2],
            ['title' => 'Configurações', 'url' => '/configuracoes', 'icon' => 'Sliders', 'order' => 3],
        ]);

        // 5. Map Menus to Permissions
        $this->mapMenusToPermissions();
    }

    protected function mapMenusToPermissions()
    {
        $mappings = [
            'Membros' => 'members.index',
            'Atas e Reuniões' => 'meetings.index',
            'Resoluções' => 'resolutions.index',
            'Entradas' => 'transactions.index',
            'Saídas' => 'transactions.index',
            'Registro de Caixa' => 'cash-register.index',
            'Plano de Contas' => 'transactions.index',
            'Orçamentos' => 'finance.budgets.index',
            'Obrigações' => 'transactions.index',
            'Nova Conferência' => 'treasury.entries.store',
            'Histórico' => 'treasury.entries.index',
            'Tesouraria' => 'treasury.entries.confirm',
            'Patrimônio' => 'patrimony.assets.index',
            'Educação Cristã' => 'ebd.classes.index',
            'Relatório Geral' => 'societies.index',
            'Controle de Acesso' => 'acl.roles.index',
            'Logs de Auditoria' => 'acl.logs.index',
            'Configurações' => 'auth.user',
        ];

        foreach ($mappings as $title => $permName) {
            $menu = Menu::where('title', $title)->first();
            if ($menu) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    $menu->permissions()->sync([$perm->id]);
                }
            }
        }

        // Map individual societies
        try {
            $allSocieties = \App\Models\Society::all();
            foreach ($allSocieties as $society) {
                $menu = Menu::where('title', $society->abbreviation)->first();
                if ($menu) {
                    // Check if specific permission exists, if not, create it
                    $permName = 'societies.' . strtolower($society->abbreviation) . '.view';
                    $perm = Permission::firstOrCreate(['name' => $permName], [
                        'group' => 'Sociedades',
                        'description' => 'Acesso à ' . $society->abbreviation
                    ]);
                    $menu->permissions()->sync([$perm->id]);
                }
            }
        } catch (\Exception $e) {}
    }
}
