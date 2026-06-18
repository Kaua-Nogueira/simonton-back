<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class NewChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpar dados existentes (Compatível com MySQL e SQLite)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        DB::table('transaction_splits')->delete();
        DB::table('transactions')->delete();
        DB::table('treasury_entries')->delete();
        DB::table('categories')->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 1. Grupo Principal - ENTRADAS
        $entradas = Category::create([
            'name' => '1. ENTRADAS',
            'type' => 'income',
            'parent_id' => null,
            'is_active' => true,
            'code' => '1'
        ]);

        $incomeSub = [
            ['name' => 'Dízimos', 'code' => '1.01'],
            ['name' => 'Ofertas', 'code' => '1.02'],
            ['name' => 'Ofertas Missionárias', 'code' => '1.03'],
            ['name' => 'Ofertas Específicas', 'code' => '1.04'],
            ['name' => 'Receitas Financeiras', 'code' => '1.05'],
            ['name' => 'Empréstimos IPB / JPEF', 'code' => '1.06'],
            ['name' => 'Parcerias', 'code' => '1.07'],
            ['name' => 'Outras Receitas', 'code' => '1.08'],
        ];

        foreach ($incomeSub as $sub) {
            Category::create([
                'name' => $sub['name'],
                'type' => 'income',
                'parent_id' => $entradas->id,
                'code' => $sub['code'],
                'is_active' => true
            ]);
        }

        // 2. Grupo Principal - SAÍDAS
        $saidas = Category::create([
            'name' => '2. SAÍDAS (DESPESAS)',
            'type' => 'expense',
            'parent_id' => null,
            'is_active' => true,
            'code' => '2'
        ]);

        $expenseData = [
            '01 - Patrimônio' => [
                ['name' => 'CAEMA', 'code' => '1050'],
                ['name' => 'CEMAR', 'code' => '1048'],
                ['name' => 'Construção e reforma', 'code' => '1044'],
                ['name' => 'Equipamentos', 'code' => '1045'],
                ['name' => 'Internet e telefonia', 'code' => '1055'],
                ['name' => 'Manutenção ar condicionado', 'code' => '1047'],
                ['name' => 'Outros Prestadores de Serviços', 'code' => '1049'],
            ],
            '02 - Sustento Pastoral' => [
                ['name' => '1/3 Férias Pastor', 'code' => null],
                ['name' => '13º Dia Pastor Presb.', 'code' => '1058'],
                ['name' => 'Ajuda de custo (Pastor)', 'code' => '1060'],
                ['name' => 'FAP', 'code' => '1059'],
                ['name' => 'Férias Pastor', 'code' => '1054'],
                ['name' => 'GPS Pastor', 'code' => '1053'],
                ['name' => 'GRF Pastor', 'code' => '1052'],
                ['name' => 'Moradia Pastoral (aluguel, taxas)', 'code' => '1057'],
                ['name' => 'Ofertas pastor', 'code' => '1051'],
                ['name' => 'Plano de Saúde Pastor', 'code' => '1061'],
                ['name' => 'Verba pastoral', 'code' => null],
            ],
            '03 - Causas Locais' => [
                ['name' => 'Depto. Infantil', 'code' => '1007'],
                ['name' => 'Despesas com bibliografia/cursos', 'code' => '1016'],
                ['name' => 'EBD', 'code' => '1014'],
                ['name' => 'Eventos', 'code' => null],
                ['name' => 'Federação de SAF/UPH', 'code' => '1011'],
                ['name' => 'Federação de UPA/UMP', 'code' => '1012'],
                ['name' => 'Ministério de louvor', 'code' => '1009'],
                ['name' => 'Músico coral Ruth Oliveira', 'code' => '1015'],
                ['name' => 'Ofertas a pregadores', 'code' => '1017'],
                ['name' => 'Outros Custos Pregadores', 'code' => '1018'],
                ['name' => 'Outros sociedade', 'code' => '1013'],
                ['name' => 'Retiro', 'code' => '1019'],
                ['name' => 'SAF', 'code' => '1005'],
                ['name' => 'UMP', 'code' => '1006'],
                ['name' => 'UPA', 'code' => '1008'],
                ['name' => 'UPH', 'code' => '1004'],
            ],
            '04 - Evangelismo Local' => [
                ['name' => 'EBF', 'code' => '1022'],
                ['name' => 'Evangelismo Local', 'code' => '1021'],
            ],
            '04 - Missões' => [
                ['name' => 'APEC', 'code' => '1024'],
                ['name' => 'Ofertas Congregações', 'code' => '1025'],
                ['name' => 'Ofertas Missionárias', 'code' => '1026'],
                ['name' => 'Verba missionária', 'code' => '1023'],
            ],
            '05 - Ação Social' => [
                ['name' => 'Ação Social', 'code' => '1003'],
                ['name' => 'Diaconia', 'code' => '1003'],
                ['name' => 'Aluguel de imóveis', 'code' => '1001'],
                ['name' => 'Outras ofertas - Ação Social', 'code' => '1002'],
            ],
            '06 - Dízimo ao Supremo Concilio' => [
                ['name' => 'Supremo Concílio', 'code' => '1020'],
            ],
            '06 - Verba Presbiterial' => [
                ['name' => 'Presbitério', 'code' => null],
            ],
            '07 - Outras Despesas' => [
                ['name' => '13º Salário Funcionários', 'code' => '1029'],
                ['name' => 'Administrador Ajuda de Custo', 'code' => '1039'],
                ['name' => 'Administrador Salário', 'code' => '1035'],
                ['name' => 'Ajuda de Custo', 'code' => '1031'],
                ['name' => 'Benefícios Sindicais', 'code' => '1037'],
                ['name' => 'Contador', 'code' => '1036'],
                ['name' => 'DARF Funcionários', 'code' => '1028'],
                ['name' => 'Despesas com papelaria', 'code' => '1032'],
                ['name' => 'Despesas manutenção', 'code' => '1033'],
                ['name' => 'eSocial', 'code' => null],
                ['name' => 'Férias Funcionários', 'code' => null],
                ['name' => 'GPS Funcionários - INSS', 'code' => '1042'],
                ['name' => 'GRF Funcionários - FGTS', 'code' => '1040'],
                ['name' => 'Impostos Financeiros', 'code' => '1038'],
                ['name' => 'Impressoras', 'code' => null],
                ['name' => 'Outras despesas', 'code' => '1034'],
                ['name' => 'Outros ADM', 'code' => '1030'],
                ['name' => 'P.F. Outros', 'code' => '1041'],
                ['name' => 'RECIBO', 'code' => '1027'],
                ['name' => 'Salário Funcionários', 'code' => '1043'],
                ['name' => 'Sindicato', 'code' => null],
                ['name' => 'TARIFAS BANCÁRIAS', 'code' => '1046'],
            ],
        ];

        foreach ($expenseData as $groupName => $subs) {
            // Criar o grupo sob SAÍDAS
            $parent = Category::create([
                'name' => $groupName,
                'type' => 'expense',
                'parent_id' => $saidas->id,
                'is_active' => true,
                'code' => substr($groupName, 0, 2) // Usar o número do grupo se disponível
            ]);

            foreach ($subs as $sub) {
                Category::create([
                    'name' => $sub['name'],
                    'type' => 'expense',
                    'parent_id' => $parent->id,
                    'code' => $sub['code'],
                    'is_active' => true
                ]);
            }
        }
    }
}
