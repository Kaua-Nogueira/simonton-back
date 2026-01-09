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

            // 4. TIPO: SOCIEDADES INTERNAS (ORGANIZAÇÕES AUXILIARES)
            // SAF
            ['name' => 'Presidente da SAF', 'type' => 'function', 'category' => 'Sociedade Interna (SAF)'],
            ['name' => 'Vice-Presidente da SAF', 'type' => 'function', 'category' => 'Sociedade Interna (SAF)'],
            ['name' => 'Secretária da SAF', 'type' => 'function', 'category' => 'Sociedade Interna (SAF)'],
            ['name' => 'Tesoureira da SAF', 'type' => 'function', 'category' => 'Sociedade Interna (SAF)'],
            ['name' => 'Conselheiro da SAF', 'type' => 'function', 'category' => 'Sociedade Interna (SAF)'],

            // UMP
            ['name' => 'Presidente da UMP', 'type' => 'function', 'category' => 'Sociedade Interna (UMP)'],
            ['name' => 'Vice-Presidente da UMP', 'type' => 'function', 'category' => 'Sociedade Interna (UMP)'],
            ['name' => 'Secretário da UMP', 'type' => 'function', 'category' => 'Sociedade Interna (UMP)'],
            ['name' => 'Tesoureiro da UMP', 'type' => 'function', 'category' => 'Sociedade Interna (UMP)'],
            ['name' => 'Conselheiro da UMP', 'type' => 'function', 'category' => 'Sociedade Interna (UMP)'],

            // UPH
            ['name' => 'Presidente da UPH', 'type' => 'function', 'category' => 'Sociedade Interna (UPH)'],
            ['name' => 'Vice-Presidente da UPH', 'type' => 'function', 'category' => 'Sociedade Interna (UPH)'],
            ['name' => 'Secretário da UPH', 'type' => 'function', 'category' => 'Sociedade Interna (UPH)'],
            ['name' => 'Tesoureiro da UPH', 'type' => 'function', 'category' => 'Sociedade Interna (UPH)'],
            ['name' => 'Conselheiro da UPH', 'type' => 'function', 'category' => 'Sociedade Interna (UPH)'],

            // UPA
            ['name' => 'Presidente da UPA', 'type' => 'function', 'category' => 'Sociedade Interna (UPA)'],
            ['name' => 'Vice-Presidente da UPA', 'type' => 'function', 'category' => 'Sociedade Interna (UPA)'],
            ['name' => 'Secretário da UPA', 'type' => 'function', 'category' => 'Sociedade Interna (UPA)'],
            ['name' => 'Tesoureiro da UPA', 'type' => 'function', 'category' => 'Sociedade Interna (UPA)'],
            ['name' => 'Conselheiro da UPA', 'type' => 'function', 'category' => 'Sociedade Interna (UPA)'],

             // UCP
             ['name' => 'Presidente da UCP', 'type' => 'function', 'category' => 'Sociedade Interna (UCP)'],
             ['name' => 'Vice-Presidente da UCP', 'type' => 'function', 'category' => 'Sociedade Interna (UCP)'],
             ['name' => 'Secretário da UCP', 'type' => 'function', 'category' => 'Sociedade Interna (UCP)'],
             ['name' => 'Tesoureiro da UCP', 'type' => 'function', 'category' => 'Sociedade Interna (UCP)'],
             ['name' => 'Conselheiro da UCP', 'type' => 'function', 'category' => 'Sociedade Interna (UCP)'],


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
