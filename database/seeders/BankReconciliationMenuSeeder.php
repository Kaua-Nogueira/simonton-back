<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class BankReconciliationMenuSeeder extends Seeder
{
    public function run(): void
    {
        Menu::create([
            'title' => 'Conciliação Bancária',
            'url' => '/financeiro/conciliacao',
            'icon' => 'RefreshCw',
            'parent_id' => 6,
            'order' => 10
        ]);
    }
}
