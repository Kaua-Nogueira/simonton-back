<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class IpbChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Grupo Principal - ENTRADAS
        $entradas = Category::updateOrCreate(
            ['name' => '1. ENTRADAS'],
            ['type' => 'income', 'parent_id' => null, 'is_active' => true]
        );

        $incomeSub = [
            ['name' => '1.01 Dízimos', 'code' => '101'],
            ['name' => '1.02 Ofertas Regulares', 'code' => '102'],
            ['name' => '1.03 Ofertas para Alvos / Específicas', 'code' => '103'],
            ['name' => '1.04 Outras Receitas (Aluguéis, Juros)', 'code' => '104'],
        ];

        foreach ($incomeSub as $sub) {
            Category::updateOrCreate(
                ['name' => $sub['name']],
                ['type' => 'income', 'parent_id' => $entradas->id, 'code' => $sub['code'], 'is_active' => true]
            );
        }

        // 2. Grupo Principal - SAÍDAS
        $saidas = Category::updateOrCreate(
            ['name' => '2. SAÍDAS (DESPESAS)'],
            ['type' => 'expense', 'parent_id' => null, 'is_active' => true]
        );

        $expenseGroups = [
            ['name' => '2.01 Sustento Pastoral', 'code' => '201', 'sub' => [
                '2.01.01 Côngruas / Ajuda de Custo',
                '2.01.02 Previdência IPB',
                '2.01.03 INSS / Plano de Saúde',
                '2.01.04 Anuidades / Férias',
                '2.01.05 Viagens e Representações'
            ]],
            ['name' => '2.02 Pessoal e Funcionários', 'code' => '202', 'sub' => [
                '2.02.01 Salários e Zeladoria',
                '2.02.02 Encargos Sociais (FGTS/PIS)',
                '2.02.03 Benefícios e Pró-labore',
                '2.02.04 13º Salário / Férias'
            ]],
            ['name' => '2.03 Manutenção e Patrimônio', 'code' => '203', 'sub' => [
                '2.03.01 Água e Esgoto',
                '2.03.02 Energia Elétrica',
                '2.03.03 Internet / Telefone',
                '2.03.04 Conservação e Pequenos Reparos',
                '2.03.05 Limpeza e Higiene',
                '2.03.06 Reformas e Edificação'
            ]],
            ['name' => '2.04 Educação Religiosa (EBD)', 'code' => '204', 'sub' => [
                '2.04.01 Revistas e Literaturas',
                '2.04.02 Escola Dominical (Materiais)',
                '2.04.03 Treinamentos e Seminários'
            ]],
            ['name' => '2.05 Sociedades Internas', 'code' => '205', 'sub' => [
                '2.05.01 SAF (Auxiliadora Feminina)',
                '2.05.02 UPH (Homens)',
                '2.05.03 UMP (Mocidade)',
                '2.05.04 UPA (Adolescentes)',
                '2.05.05 UCP (Crianças)'
            ]],
            ['name' => '2.06 Evangelismo e Missões', 'code' => '206', 'sub' => [
                '2.06.01 CME (Conselho Missionário)',
                '2.06.02 Missionários Campo Local',
                '2.06.03 Agências Missionárias (transcultural)',
                '2.06.04 Projetos de Evangelismo'
            ]],
            ['name' => '2.07 Liturgia e Música', 'code' => '207', 'sub' => [
                '2.07.01 Equipamentos de Som / Projeção',
                '2.07.02 Instrumentos Musicais',
                '2.07.03 Coral e Equipes de Canto'
            ]],
            ['name' => '2.08 Administração e Expediente', 'code' => '208', 'sub' => [
                '2.08.01 Papelaria e Escritório',
                '2.08.02 Softwares e Licenças',
                '2.08.03 Tarifas Bancárias',
                '2.08.04 Taxas e Impostos'
            ]],
            ['name' => '2.09 Diaconia e Assistência', 'code' => '209', 'sub' => [
                '2.09.01 Cestas Básicas',
                '2.09.02 Medicamentos e Auxílios',
                '2.09.03 Donativos de Emergência'
            ]],
            ['name' => '2.10 Repasses Conciliares', 'code' => '210', 'sub' => [
                '2.10.01 Contribuições Presbitério',
                '2.10.10 Supremo Concílio (SC-IPB)',
                '2.10.20 Sínodo'
            ]],
        ];

        foreach ($expenseGroups as $group) {
            $parent = Category::updateOrCreate(
                ['name' => $group['name']],
                ['type' => 'expense', 'parent_id' => $saidas->id, 'code' => $group['code'], 'is_active' => true]
            );

            foreach ($group['sub'] as $index => $subName) {
                Category::updateOrCreate(
                    ['name' => $subName],
                    [
                        'type' => 'expense', 
                        'parent_id' => $parent->id, 
                        'code' => $group['code'] . '.' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                        'is_active' => true
                    ]
                );
            }
        }
    }
}
