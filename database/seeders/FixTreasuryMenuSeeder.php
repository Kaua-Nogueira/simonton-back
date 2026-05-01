<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class FixTreasuryMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Find main Treasury menu
        $treasuryBranch = Menu::where('title', 'Tesouraria')->whereNull('parent_id')->first();

        if ($treasuryBranch) {
            // Update parent to have no URL so it acts as a folder
            $treasuryBranch->update([
                'url' => null,
                'icon' => 'Banknote'
            ]);

            // Create or update "Para Revisão" (Pending) submenu
            Menu::updateOrCreate(
                ['parent_id' => $treasuryBranch->id, 'title' => 'Para Revisão'],
                [
                    'url' => '/tesouraria',
                    'icon' => 'Clock',
                    'order' => 1
                ]
            );

            // Create or update "Aprovados" (History) submenu
            Menu::updateOrCreate(
                ['parent_id' => $treasuryBranch->id, 'title' => 'Aprovados'],
                [
                    'url' => '/tesouraria/historico',
                    'icon' => 'CheckSquare',
                    'order' => 2
                ]
            );

            // Add "Resumo Financeiro" for better visibility if they want
            /*
            Menu::updateOrCreate(
                ['parent_id' => $treasuryBranch->id, 'title' => 'Fluxo de Caixa'],
                [
                    'url' => '/cash-register',
                    'icon' => 'TrendingUp',
                    'order' => 3
                ]
            );
            */
        }
    }
}
