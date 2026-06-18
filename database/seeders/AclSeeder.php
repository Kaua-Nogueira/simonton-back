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

        $treasurerPerms = Permission::where(function($q) {
            $q->where('group', 'LIKE', '%Financeiro%')
              ->orWhere('group', 'LIKE', '%Tesouraria%')
              ->orWhere('group', 'LIKE', '%Categories%')
              ->orWhere('group', 'LIKE', '%Obligations%')
              ->orWhere('name', 'LIKE', 'finance.%');
        })->pluck('id');
        $treasurer->permissions()->sync($treasurerPerms);

        $secretaryPerms = Permission::where(function($q) {
            $q->where('group', 'LIKE', '%Secretaria%')
              ->orWhere('group', 'LIKE', '%Educação%')
              ->orWhere('group', 'LIKE', '%Ebd%')
              ->orWhere('group', 'LIKE', '%Membr%')
              ->orWhere('group', 'LIKE', '%Patrimon%')
              ->orWhere('name', 'LIKE', 'members.%')
              ->orWhere('name', 'LIKE', 'meetings.%');
        })->pluck('id');
        $secretary->permissions()->sync($secretaryPerms);

        // 4. Create Menus
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Menu::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

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
            ['title' => 'Agenda Eclesiástica', 'url' => '/agenda', 'icon' => 'Calendar', 'order' => 4],
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
            ['title' => 'Contas a Pagar', 'url' => '/financeiro/contas-pagar', 'icon' => 'Receipt', 'order' => 3], // Adicionado
            ['title' => 'Conciliação Bancária', 'url' => '/financeiro/conciliacao', 'icon' => 'RefreshCw', 'order' => 4], // Adicionado
            ['title' => 'Registro de Caixa', 'url' => '/registro-caixa', 'icon' => 'Wallet', 'order' => 5],
            ['title' => 'Plano de Contas', 'url' => '/financeiro/plano-de-contas', 'icon' => 'ListTree', 'order' => 6],
            ['title' => 'Orçamentos', 'url' => '/financeiro/orcamento', 'icon' => 'BarChart3', 'order' => 7],
            ['title' => 'Obrigações', 'url' => '/financeiro/obrigacoes', 'icon' => 'Calendar', 'order' => 8],
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

        // Patrimônio
        $patrimonio = Menu::create([
            'title' => 'Patrimônio',
            'url' => '#',
            'icon' => 'Archive',
            'order' => 6
        ]);

        $patrimonio->children()->createMany([
            ['title' => 'Bens (Ativos)', 'url' => '/patrimonio', 'icon' => 'PackageOpen', 'order' => 1],
            ['title' => 'Inventário', 'url' => '/patrimonio/inventario', 'icon' => 'ClipboardList', 'order' => 2],
            ['title' => 'Locais', 'url' => '/patrimonio/locais', 'icon' => 'MapPin', 'order' => 3],
            ['title' => 'Categorias', 'url' => '/patrimonio/cadastros', 'icon' => 'Tags', 'order' => 4],
            ['title' => 'Manutenções', 'url' => '/patrimonio/manutencao', 'icon' => 'Wrench', 'order' => 5],
            ['title' => 'Empréstimos', 'url' => '/patrimonio/emprestimos', 'icon' => 'ArrowLeftRight', 'order' => 6],
        ]);

        // Educação Cristã
        $ebd = Menu::create([
            'title' => 'Educação Cristã',
            'url' => '#',
            'icon' => 'BookOpen',
            'order' => 7
        ]);

        $ebd->children()->createMany([
            ['title' => 'Classes', 'url' => '/ebd', 'icon' => 'Users', 'order' => 1],
            ['title' => 'Relatórios EBD', 'url' => '/ebd/relatorios', 'icon' => 'BarChart3', 'order' => 2],
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
            'Agenda Eclesiástica' => 'calendar.events.index',
            'Entradas' => 'transactions.index',
            'Saídas' => 'transactions.index',
            'Contas a Pagar' => 'finance.contas-pagar.index',
            'Conciliação Bancária' => 'reconciliation.pending',
            'Registro de Caixa' => 'cash-register.index',
            'Plano de Contas' => 'categories.index',
            'Orçamentos' => 'finance.budgets.index',
            'Obrigações' => 'societies.obligations.index',
            'Nova Conferência' => 'treasury.entries.store',
            'Histórico' => 'treasury.entries.index',
            'Tesouraria' => 'treasury.entries.confirm',
            'Bens (Ativos)' => 'patrimony.assets.index',
            'Inventário' => 'patrimony.consumables.index',
            'Locais' => 'patrimony.locations.index',
            'Categorias' => 'patrimony.categories.index',
            'Manutenções' => 'patrimony.maintenance.requests.index',
            'Empréstimos' => 'patrimony.loans.index',
            'Classes' => 'ebd.classes.index',
            'Relatórios EBD' => 'ebd.classes.index',
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
