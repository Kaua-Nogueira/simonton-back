<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class UpdateMenuOrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            2  => 1,  // Dashboard
            13 => 2,  // Financeiro
            16 => 3,  // Tesouraria
            23 => 4,  // Sociedades
            30 => 5,  // Patrimônio
            20 => 6,  // Diaconia
            36 => 7,  // EBD
            39 => 8,  // Relatórios
            43 => 9,  // Configurações
            41 => 10, // Admin
            1  => 11, // Apresentação
        ];

        foreach ($orders as $id => $order) {
            Menu::where('id', $id)->update(['order' => $order]);
        }
    }
}
