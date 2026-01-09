<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\Route;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions/routes
        $permissions = [
            // Finance - Transactions
            ['name' => 'transactions.index', 'group' => 'finance', 'description' => 'Visualizar Transações'],
            ['name' => 'transactions.show', 'group' => 'finance', 'description' => 'Detalhes de Transação'],
            ['name' => 'transactions.store', 'group' => 'finance', 'description' => 'Criar Transação'],
            ['name' => 'transactions.import', 'group' => 'finance', 'description' => 'Importar OFX'],
            ['name' => 'transactions.confirm', 'group' => 'finance', 'description' => 'Confirmar/Conciliar Transação'],
            ['name' => 'transactions.split', 'group' => 'finance', 'description' => 'Desmembrar Transação'],
            
            // Finance - Pending
            ['name' => 'transactions.pending', 'group' => 'finance', 'description' => 'Ver Transações Pendentes'],

            // Finance - Categories
            ['name' => 'categories.index', 'group' => 'finance', 'description' => 'Gerenciar Categorias'],
            ['name' => 'categories.store', 'group' => 'finance', 'description' => 'Criar Categoria'],
            ['name' => 'categories.update', 'group' => 'finance', 'description' => 'Editar Categoria'],
            ['name' => 'categories.destroy', 'group' => 'finance', 'description' => 'Excluir Categoria'],

            // Finance - Cost Centers
            ['name' => 'cost-centers.index', 'group' => 'finance', 'description' => 'Gerenciar Centros de Custo'],
            ['name' => 'cost-centers.store', 'group' => 'finance', 'description' => 'Criar Centro de Custo'],
            ['name' => 'cost-centers.update', 'group' => 'finance', 'description' => 'Editar Centro de Custo'],
            ['name' => 'cost-centers.destroy', 'group' => 'finance', 'description' => 'Excluir Centro de Custo'],

            // Finance - Cash Register
            ['name' => 'cash-register.index', 'group' => 'finance', 'description' => 'Ver Livro Caixa'],
            ['name' => 'cash-register.balance', 'group' => 'finance', 'description' => 'Ver Saldo Atual'],

            // Finance - Budgets
            ['name' => 'finance.budgets.index', 'group' => 'finance', 'description' => 'Listar Orçamentos'],
            ['name' => 'finance.budgets.store', 'group' => 'finance', 'description' => 'Criar Orçamento'],
            ['name' => 'finance.budgets.update', 'group' => 'finance', 'description' => 'Atualizar Orçamento'],
            ['name' => 'finance.budgets.show', 'group' => 'finance', 'description' => 'Ver Detalhes do Orçamento'],
            ['name' => 'finance.budgets.items', 'group' => 'finance', 'description' => 'Ver Itens do Orçamento'],
            ['name' => 'finance.budgets.items.store', 'group' => 'finance', 'description' => 'Adicionar Item ao Orçamento'],
            ['name' => 'finance.budgets.movements.store', 'group' => 'finance', 'description' => 'Registrar Movimentação no Orçamento'],
            ['name' => 'finance.budgets.status', 'group' => 'finance', 'description' => 'Ver Status do Orçamento'],

            // Finance - Remittances
            ['name' => 'finance.remittances.index', 'group' => 'finance', 'description' => 'Listar Remessas'],
            ['name' => 'finance.remittances.show', 'group' => 'finance', 'description' => 'Ver Detalhes de Remessa'],
            ['name' => 'finance.remittances.preview', 'group' => 'finance', 'description' => 'Pré-visualizar Remessa'],
            ['name' => 'finance.remittances.generate', 'group' => 'finance', 'description' => 'Gerar Remessa'],

            // Members
            ['name' => 'members.index', 'group' => 'members', 'description' => 'Listar Membros'],
            ['name' => 'members.show', 'group' => 'members', 'description' => 'Ver Perfil de Membro'],
            ['name' => 'members.store', 'group' => 'members', 'description' => 'Cadastrar Membro'],
            ['name' => 'members.update', 'group' => 'members', 'description' => 'Atualizar Dados de Membro'],
            ['name' => 'members.contributions', 'group' => 'members', 'description' => 'Ver Contribuições do Membro'],
            ['name' => 'members.transfer-letter', 'group' => 'members', 'description' => 'Gerar Carta de Transferência'],
            
            // Members - System Access
            ['name' => 'members.user.store', 'group' => 'members', 'description' => 'Criar Usuário para Membro'],
            ['name' => 'members.user.update', 'group' => 'members', 'description' => 'Atualizar Usuário de Membro'],
            ['name' => 'members.user.destroy', 'group' => 'members', 'description' => 'Revogar Acesso de Sistema'],

            // Members - Roles (Assignments)
            ['name' => 'roles.assign', 'group' => 'members', 'description' => 'Atribuir Cargo a Membro'],
            ['name' => 'roles.revoke', 'group' => 'members', 'description' => 'Remover Cargo de Membro'],
            ['name' => 'roles.history', 'group' => 'members', 'description' => 'Ver Histórico de Cargos'],

            // Patrimony - Locations
            ['name' => 'patrimony.locations.index', 'group' => 'patrimony', 'description' => 'Listar Locais'],
            ['name' => 'patrimony.locations.store', 'group' => 'patrimony', 'description' => 'Criar Local'],
            ['name' => 'patrimony.locations.update', 'group' => 'patrimony', 'description' => 'Editar Local'],
            ['name' => 'patrimony.locations.destroy', 'group' => 'patrimony', 'description' => 'Excluir Local'],

            // Patrimony - Categories
            ['name' => 'patrimony.categories.index', 'group' => 'patrimony', 'description' => 'Categorias de Patrimônio'],
            ['name' => 'patrimony.categories.store', 'group' => 'patrimony', 'description' => 'Criar Categoria de Patrimônio'],
            ['name' => 'patrimony.categories.update', 'group' => 'patrimony', 'description' => 'Editar Categoria de Patrimônio'],
            ['name' => 'patrimony.categories.destroy', 'group' => 'patrimony', 'description' => 'Excluir Categoria de Patrimônio'],

            // Patrimony - Assets
            ['name' => 'patrimony.assets.index', 'group' => 'patrimony', 'description' => 'Listar Bens'],
            ['name' => 'patrimony.assets.store', 'group' => 'patrimony', 'description' => 'Cadastrar Bem'],
            ['name' => 'patrimony.assets.show', 'group' => 'patrimony', 'description' => 'Detalhes do Bem'],
            ['name' => 'patrimony.assets.update', 'group' => 'patrimony', 'description' => 'Atualizar Bem'], // Note: API resource implies .update / .destroy usually exist
            ['name' => 'patrimony.assets.destroy', 'group' => 'patrimony', 'description' => 'Excluir Bem'],

            // Patrimony - Maintenance
            ['name' => 'patrimony.maintenance.requests.index', 'group' => 'patrimony', 'description' => 'Listar Solicitações de Manutenção'],
            ['name' => 'patrimony.maintenance.requests.store', 'group' => 'patrimony', 'description' => 'Criar Solicitação de Manutenção'],
            ['name' => 'patrimony.maintenance.requests.show', 'group' => 'patrimony', 'description' => 'Ver Solicitação de Manutenção'],
            ['name' => 'patrimony.maintenance.requests.update', 'group' => 'patrimony', 'description' => 'Atualizar Solicitação de Manutenção'],
            ['name' => 'patrimony.maintenance.schedules.index', 'group' => 'patrimony', 'description' => 'Ver Agenda de Manutenção'],
            ['name' => 'patrimony.maintenance.schedules.store', 'group' => 'patrimony', 'description' => 'Agendar Manutenção'],

            // Patrimony - Loans
            ['name' => 'patrimony.loans.index', 'group' => 'patrimony', 'description' => 'Listar Empréstimos'],
            ['name' => 'patrimony.loans.store', 'group' => 'patrimony', 'description' => 'Registrar Empréstimo'],
            ['name' => 'patrimony.loans.return', 'group' => 'patrimony', 'description' => 'Devolver Empréstimo'],

            // Patrimony - Spaces
            ['name' => 'patrimony.spaces.bookings.index', 'group' => 'patrimony', 'description' => 'Ver Reservas de Espaço'],
            ['name' => 'patrimony.spaces.bookings.store', 'group' => 'patrimony', 'description' => 'Solicitar Reserva'],
            ['name' => 'patrimony.spaces.bookings.status', 'group' => 'patrimony', 'description' => 'Aprovar/Rejeitar Reserva'],

            // Patrimony - Consumables
            ['name' => 'patrimony.consumables.index', 'group' => 'patrimony', 'description' => 'Estoque de Consumo'],
            ['name' => 'patrimony.consumables.store', 'group' => 'patrimony', 'description' => 'Adicionar Item de Consumo'],
            ['name' => 'patrimony.consumables.update', 'group' => 'patrimony', 'description' => 'Atualizar Item de Consumo'],
            ['name' => 'patrimony.consumables.destroy', 'group' => 'patrimony', 'description' => 'Excluir Item de Consumo'],

            // Secretariat - Meetings
            ['name' => 'meetings.index', 'group' => 'secretariat', 'description' => 'Listar Atas/Reuniões'],
            ['name' => 'meetings.store', 'group' => 'secretariat', 'description' => 'Criar Ata'],
            ['name' => 'meetings.show', 'group' => 'secretariat', 'description' => 'Ver Ata'],
            ['name' => 'meetings.update', 'group' => 'secretariat', 'description' => 'Editar Ata'],
            ['name' => 'meetings.destroy', 'group' => 'secretariat', 'description' => 'Excluir Ata'],
            ['name' => 'meetings.populate', 'group' => 'secretariat', 'description' => 'Preencher Presença em Massa'],

            // Secretariat - Resolutions
            ['name' => 'resolutions.index', 'group' => 'secretariat', 'description' => 'Listar Resoluções'],
            ['name' => 'resolutions.store', 'group' => 'secretariat', 'description' => 'Criar Resolução'],
            ['name' => 'resolutions.show', 'group' => 'secretariat', 'description' => 'Ver Resolução'],
            ['name' => 'resolutions.update', 'group' => 'secretariat', 'description' => 'Editar Resolução'],
            ['name' => 'resolutions.destroy', 'group' => 'secretariat', 'description' => 'Excluir Resolução'],

            // Internal Societies
            ['name' => 'societies.index', 'group' => 'societies', 'description' => 'Listar Sociedades Internas'],
            ['name' => 'societies.store', 'group' => 'societies', 'description' => 'Criar Sociedade'],
            ['name' => 'societies.show', 'group' => 'societies', 'description' => 'Ver Sociedade'],
            ['name' => 'societies.update', 'group' => 'societies', 'description' => 'Editar Sociedade'],
            ['name' => 'societies.destroy', 'group' => 'societies', 'description' => 'Excluir Sociedade'],
            
            ['name' => 'societies.members.index', 'group' => 'societies', 'description' => 'Sócios da SI'],
            ['name' => 'societies.members.store', 'group' => 'societies', 'description' => 'Adicionar Sócio'],
            ['name' => 'societies.members.show', 'group' => 'societies', 'description' => 'Ver Sócio'],
            ['name' => 'societies.members.update', 'group' => 'societies', 'description' => 'Atualizar Sócio'],
            ['name' => 'societies.members.destroy', 'group' => 'societies', 'description' => 'Remover Sócio'],

            ['name' => 'societies.mandates.index', 'group' => 'societies', 'description' => 'Mandatos da SI'],
            ['name' => 'societies.mandates.store', 'group' => 'societies', 'description' => 'Criar Mandato'],
            ['name' => 'societies.mandates.show', 'group' => 'societies', 'description' => 'Ver Mandato'],
            ['name' => 'societies.mandates.update', 'group' => 'societies', 'description' => 'Atualizar Mandato'],
            ['name' => 'societies.mandates.destroy', 'group' => 'societies', 'description' => 'Excluir Mandato'],
            ['name' => 'societies.mandates.roles.add', 'group' => 'societies', 'description' => 'Adicionar Função ao Mandato'],
            ['name' => 'societies.mandates.roles.remove', 'group' => 'societies', 'description' => 'Remover Função do Mandato'],

            ['name' => 'societies.financial.index', 'group' => 'societies', 'description' => 'Financeiro da SI'],
            ['name' => 'societies.financial.movements', 'group' => 'societies', 'description' => 'Registrar Movimento SI'],
            ['name' => 'societies.financial.dues', 'group' => 'societies', 'description' => 'Ver Mensalidades'],
            ['name' => 'societies.financial.pay-dues', 'group' => 'societies', 'description' => 'Pagar Mensalidade'],

            // Treasury / Diaconia (Conferences)
            ['name' => 'treasury.entries.index', 'group' => 'treasury', 'description' => 'Listar Conferências'],
            ['name' => 'treasury.entries.store', 'group' => 'treasury', 'description' => 'Iniciar Conferência'],
            ['name' => 'treasury.entries.show', 'group' => 'treasury', 'description' => 'Visualizar Conferência'],
            ['name' => 'treasury.entries.cash.update', 'group' => 'treasury', 'description' => 'Contagem de Caixa'],
            ['name' => 'treasury.entries.splits.add', 'group' => 'treasury', 'description' => 'Adicionar Envelope/Desmembramento'],
            ['name' => 'treasury.entries.splits.remove', 'group' => 'treasury', 'description' => 'Remover Envelope'],
            ['name' => 'treasury.entries.submit', 'group' => 'treasury', 'description' => 'Submeter Conferência'],
            ['name' => 'treasury.entries.confirm', 'group' => 'treasury', 'description' => 'Aprovar Conferência (Tesoureiro)'],

            // EBD
            ['name' => 'ebd.classes.index', 'group' => 'ebd', 'description' => 'Listar Classes EBD'],
            ['name' => 'ebd.classes.show', 'group' => 'ebd', 'description' => 'Ver Classe EBD'],
            ['name' => 'ebd.classes.attendance', 'group' => 'ebd', 'description' => 'Chamada EBD'],

            // ACL & System
            ['name' => 'acl.permissions.index', 'group' => 'system', 'description' => 'Listar Permissões'],
            ['name' => 'acl.permissions.scan', 'group' => 'system', 'description' => 'Escanear Permissões'],
            ['name' => 'acl.roles.index', 'group' => 'system', 'description' => 'Listar Papéis'],
            ['name' => 'acl.roles.store', 'group' => 'system', 'description' => 'Criar Papel'],
            ['name' => 'acl.roles.show', 'group' => 'system', 'description' => 'Ver Papel'],
            ['name' => 'acl.roles.update', 'group' => 'system', 'description' => 'Atualizar Papel'],
            ['name' => 'acl.roles.destroy', 'group' => 'system', 'description' => 'Excluir Papel'],
            ['name' => 'acl.users.index', 'group' => 'system', 'description' => 'Listar Usuários'],
            ['name' => 'acl.users.update', 'group' => 'system', 'description' => 'Gerenciar Usuário'],
            ['name' => 'acl.menus.index', 'group' => 'system', 'description' => 'Gerenciar Menus'],
            ['name' => 'acl.menus.store', 'group' => 'system', 'description' => 'Criar Menu'],
            ['name' => 'acl.menus.show', 'group' => 'system', 'description' => 'Ver Menu'],
            ['name' => 'acl.menus.update', 'group' => 'system', 'description' => 'Atualizar Menu'],
            ['name' => 'acl.menus.destroy', 'group' => 'system', 'description' => 'Excluir Menu'],
            ['name' => 'acl.menus.reorder', 'group' => 'system', 'description' => 'Reordenar Menus'],
            ['name' => 'acl.logs.index', 'group' => 'system', 'description' => 'Logs de Auditoria'],
            
            // Dashboard & Reports
            ['name' => 'dashboard.stats', 'group' => 'dashboard', 'description' => 'Estatísticas do Dashboard'],
            ['name' => 'reports.view', 'group' => 'reports', 'description' => 'Visualizar Relatórios'],

            // Notifications
            ['name' => 'notifications.index', 'group' => 'system', 'description' => 'Ver Notificações'],
            ['name' => 'notifications.read', 'group' => 'system', 'description' => 'Marcar Notificação como Lida'],
            ['name' => 'notifications.read-all', 'group' => 'system', 'description' => 'Marcar Todas Notificações'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name']],
                ['group' => $p['group'], 'description' => $p['description']]
            );
        }
    }
}
