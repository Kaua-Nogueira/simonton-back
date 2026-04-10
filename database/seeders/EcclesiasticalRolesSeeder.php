<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class EcclesiasticalRolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            // 1. TIPO: OFÍCIOS ECLESIÁSTICOS (Permanentes | Constitucionais | Governo e/ou serviço)
            ['name' => 'Pastor (Presbítero Docente)', 'type' => 'office', 'category' => 'Ofícios Eclesiásticos'],
            ['name' => 'Presbítero Regente', 'type' => 'office', 'category' => 'Ofícios Eclesiásticos'],
            ['name' => 'Diácono', 'type' => 'office', 'category' => 'Ofícios Eclesiásticos'],

            // 2. TIPO: GOVERNO DA IGREJA LOCAL (CONSELHO)
            ['name' => 'Moderador do Conselho', 'type' => 'function', 'category' => 'Conselho (Governo)'],
            ['name' => 'Vice-Moderador do Conselho', 'type' => 'function', 'category' => 'Conselho (Governo)'],
            ['name' => 'Secretário do Conselho', 'type' => 'function', 'category' => 'Conselho (Governo)'],
            ['name' => 'Vice-Secretário do Conselho', 'type' => 'function', 'category' => 'Conselho (Governo)'],
            ['name' => 'Membro do Conselho', 'type' => 'function', 'category' => 'Conselho (Governo)'],

            // 3. TIPO: JUNTA DIACONAL (Serviço e misericórdia | Apenas diáconos)
            ['name' => 'Presidente da Junta Diaconal', 'type' => 'function', 'category' => 'Junta Diaconal'],
            ['name' => 'Vice-Presidente da Junta Diaconal', 'type' => 'function', 'category' => 'Junta Diaconal'],
            ['name' => 'Secretário da Junta Diaconal', 'type' => 'function', 'category' => 'Junta Diaconal'],
            ['name' => 'Tesoureiro da Junta Diaconal', 'type' => 'function', 'category' => 'Junta Diaconal'],
            ['name' => 'Membro da Junta Diaconal', 'type' => 'function', 'category' => 'Junta Diaconal'],
            
            // 5. TIPO: EDUCAÇÃO CRISTÃ (EBD)
            ['name' => 'Superintendente da EBD', 'type' => 'function', 'category' => 'Educação Cristã (EBD)'],
            ['name' => 'Vice-Superintendente da EBD', 'type' => 'function', 'category' => 'Educação Cristã (EBD)'],
            ['name' => 'Secretário da EBD', 'type' => 'function', 'category' => 'Educação Cristã (EBD)'],
            ['name' => 'Professor da EBD', 'type' => 'function', 'category' => 'Educação Cristã (EBD)'],
            ['name' => 'Auxiliar de Classe', 'type' => 'function', 'category' => 'Educação Cristã (EBD)'],

            // 6. TIPO: MINISTÉRIOS E DEPARTAMENTOS DA IGREJA
            ['name' => 'Líder de Ministério', 'type' => 'function', 'category' => 'Ministérios'],
            ['name' => 'Vice-Líder de Ministério', 'type' => 'function', 'category' => 'Ministérios'],
            ['name' => 'Secretário de Ministério', 'type' => 'function', 'category' => 'Ministérios'],
            ['name' => 'Tesoureiro de Ministério', 'type' => 'function', 'category' => 'Ministérios'],
            ['name' => 'Membro de Ministério', 'type' => 'function', 'category' => 'Ministérios'],

            // 7. TIPO: REPRESENTAÇÃO ECLESIÁSTICA (CONCÍLIOS)
            ['name' => 'Comissário ao Presbitério', 'type' => 'function', 'category' => 'Representação (Concílios)'],
            ['name' => 'Suplente de Comissário', 'type' => 'function', 'category' => 'Representação (Concílios)'],
            ['name' => 'Membro de Comissão Presbiterial', 'type' => 'function', 'category' => 'Representação (Concílios)'],
            ['name' => 'Secretário Presbiterial', 'type' => 'function', 'category' => 'Representação (Concílios)'],
            ['name' => 'Moderador de Concílio', 'type' => 'function', 'category' => 'Representação (Concílios)'],

            // 8. TIPO: ADMINISTRAÇÃO ECLESIÁSTICA
            ['name' => 'Tesoureiro da Igreja', 'type' => 'function', 'category' => 'Administração'],
            ['name' => 'Secretário Administrativo', 'type' => 'function', 'category' => 'Administração'],
            ['name' => 'Responsável por Patrimônio', 'type' => 'function', 'category' => 'Administração'],
            ['name' => 'Responsável por Cadastro de Membros', 'type' => 'function', 'category' => 'Administração'],
            ['name' => 'Responsável por TI / Sistema', 'type' => 'function', 'category' => 'Administração'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], 
                [
                    'type' => $role['type'],
                    'category' => $role['category']
                ]
            );
        }
    }
}
