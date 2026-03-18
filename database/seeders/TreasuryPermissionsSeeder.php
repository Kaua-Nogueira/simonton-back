<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class TreasuryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'treasury.entries.index'   => 'Ver fila de conferências (Tesouraria)',
            'treasury.entries.show'    => 'Ver detalhes da conferência',
            'treasury.entries.confirm' => 'Confirmar/Auditar conferência',
        ];

        foreach ($permissions as $name => $desc) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $desc, 'group' => 'Tesouraria']
            );
        }

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first() ?: Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', array_keys($permissions))->pluck('id')
            );
        }

        // Assign to Tesoureiro role
        $treasurerRole = Role::where('name', 'Tesoureiro')->first();
        if ($treasurerRole) {
            $treasurerRole->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', array_keys($permissions))->pluck('id')
            );
        }
    }
}
