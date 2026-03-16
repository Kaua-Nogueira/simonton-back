<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class TreasuryMenuSeeder extends Seeder
{
    public function run(): void
    {
        $m = Menu::find(16);
        if ($m) {
            $m->update([
                'url' => null,
                'icon' => 'Banknote'
            ]);
        }

        Menu::create([
            'title' => 'Para Aprovar',
            'url' => '/tesouraria',
            'icon' => 'Clock',
            'parent_id' => 16,
            'order' => 1
        ]);

        Menu::create([
            'title' => 'Histórico de Aprovados',
            'url' => '/tesouraria/historico',
            'icon' => 'History',
            'parent_id' => 16,
            'order' => 2
        ]);

        Menu::create([
            'title' => 'Nova Conferência',
            'url' => '/diaconia',
            'icon' => 'ArrowUpRight',
            'parent_id' => 16,
            'order' => 3
        ]);
    }
}
