<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class FinancialCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Grupo: "01 - Patrimônio"
        $patrimonio = Category::firstOrCreate(
            ['name' => '01 - Patrimônio'], // Unique by name for Groups
            [
                'type' => 'expense',
                'description' => 'Despesas com patrimônio e manutenção',
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        $patrimonioItems = [
            ['name' => 'Água/Esgoto (CAEMA)', 'code' => '1050'],
            ['name' => 'Energia (CEMAR)', 'code' => '1048'],
            ['name' => 'Construção e Reforma', 'code' => '1044'],
        ];

        foreach ($patrimonioItems as $item) {
            Category::updateOrCreate(
                ['code' => $item['code']], // Identify by unique code
                [
                    'name' => $item['name'],
                    'type' => 'expense',
                    'parent_id' => $patrimonio->id,
                    'is_active' => true,
                ]
            );
        }

        // Grupo: "02 - Sustento Pastoral"
        $sustento = Category::firstOrCreate(
            ['name' => '02 - Sustento Pastoral'],
            [
                'type' => 'expense',
                'description' => 'Despesas com sustento pastoral',
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        $sustentoItems = [
            ['name' => 'Férias Pastor', 'code' => '1054'],
            ['name' => 'Plano de Saúde', 'code' => '1061'],
        ];

        foreach ($sustentoItems as $item) {
            Category::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'type' => 'expense',
                    'parent_id' => $sustento->id,
                    'is_active' => true,
                ]
            );
        }
        
        // Example Income Group (just to have one, though not explicitly asked in seed list, user asked for structure)
        // I will stick to what was asked.
    }
}
