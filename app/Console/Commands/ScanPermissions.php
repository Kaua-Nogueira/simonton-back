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
            if (!$name || str_starts_with($name, 'sanctum.') || str_starts_with($name, 'ignition.') || str_starts_with($name, 'acl.')) {
                continue;
            }

            // Extract group and sub-action
            $parts = explode('.', $name);
            $mainGroup = $parts[0];
            $action = end($parts);

            // Mapping groups to readable names
            $groupNames = [
                'transactions' => 'Financeiro (Transações)',
                'members' => 'Secretaria (Membros)',
                'categories' => 'Financeiro (Categorias)',
                'cost-centers' => 'Financeiro (Centros de Custo)',
                'cash-register' => 'Financeiro (Registro de Caixa)',
                'dashboard' => 'Geral (Dashboard)',
                'ebd' => 'Educação (EBD)',
                'reports' => 'Geral (Relatórios)',
                'meetings' => 'Secretaria (Atas/Reuniões)',
                'resolutions' => 'Secretaria (Resoluções)',
                'societies' => 'Sociedades Internas',
                'patrimony' => 'Patrimônio',
                'finance' => 'Financeiro (Orçamentos)',
                'treasury' => 'Tesouraria (Diaconia)',
                'notifications' => 'Geral (Notificações)',
            ];

            // Mapping actions to readable verbs
            $actionNames = [
                'index' => 'Visualizar Lista',
                'show' => 'Visualizar Detalhes',
                'store' => 'Criar Novo',
                'update' => 'Editar Registro',
                'destroy' => 'Excluir Registro',
                'confirm' => 'Confirmar/Aprovar',
                'pending' => 'Ver Pendentes',
                'pdf' => 'Gerar PDF',
                'import' => 'Importar Dados',
                'stats' => 'Ver Estatísticas',
            ];

            $displayName = ($groupNames[$mainGroup] ?? ucfirst($mainGroup)) . ': ' . ($actionNames[$action] ?? ucfirst($action));
            $group = $groupNames[$mainGroup] ?? ucfirst($mainGroup);

            // Extract method (get, post, put, delete)
            $methods = $route->methods();
            $methods = array_filter($methods, fn($m) => $m !== 'HEAD');
            $method = reset($methods) ?: 'GET';

            \App\Models\Permission::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'group' => $group,
                    'method' => $method,
                    'description' => "Permissão para acessar a rota {$name} ({$method})"
                ]
            );
            $count++;
        }

        $this->info("Scanned and updated {$count} permissions.");
    }
}
