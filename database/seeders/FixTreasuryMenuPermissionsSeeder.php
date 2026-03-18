<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Permission;

class FixTreasuryMenuPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $pIndex = Permission::where('name', 'treasury.entries.index')->first();
        
        // Parent: Tesouraria
        $parent = Menu::find(16);
        if ($parent && $pIndex) {
            $parent->permissions()->syncWithoutDetaching([$pIndex->id]);
        }

        // Child: Para Aprovar
        $paraAprovar = Menu::where('title', 'Para Aprovar')->where('parent_id', 16)->first();
        if ($paraAprovar && $pIndex) {
            $paraAprovar->permissions()->syncWithoutDetaching([$pIndex->id]);
        }

        // Child: Histórico
        $historico = Menu::where('title', 'Histórico de Aprovados')->where('parent_id', 16)->first();
        if ($historico && $pIndex) {
            $historico->permissions()->syncWithoutDetaching([$pIndex->id]);
        }
    }
}
