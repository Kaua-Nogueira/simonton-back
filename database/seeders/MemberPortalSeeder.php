<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Menu;

class MemberPortalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create specific permissions for the portal
        $perms = [
            ['name' => 'members.portal.me', 'group' => 'Membro', 'description' => 'Visualizar próprio perfil (Portal)'],
            ['name' => 'members.portal.contributions', 'group' => 'Membro', 'description' => 'Visualizar próprias contribuições (Portal)'],
            ['name' => 'members.generate-access', 'group' => 'Gestão', 'description' => 'Gerar acesso para membros'],
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p['name']], $p);
        }

        // 2. Create the Member Role
        $memberRole = Role::firstOrCreate(['name' => 'Membro (Sistema)'], [
            'type' => 'system',
            'description' => 'Acesso restrito para membros verificarem seus dados e ofertas'
        ]);

        $memberRole->permissions()->sync(
            Permission::whereIn('name', ['members.portal.me', 'members.portal.contributions'])->pluck('id')
        );

        // 3. Give access generation permission to existing system roles
        $managementRoles = Role::whereIn('name', ['Admin', 'Tesoureiro (Sistema)', 'Secretário (Sistema)'])->get();
        $genAccessPerm = Permission::where('name', 'members.generate-access')->first();

        foreach ($managementRoles as $role) {
            $role->permissions()->attach($genAccessPerm->id);
        }

        // 4. Create Portal Menus
        $portalMenu = Menu::firstOrCreate(['title' => 'Meu Portal'], [
            'url' => '#',
            'icon' => 'Shield',
            'order' => 100
        ]);
        
        $portalMenu->permissions()->sync(
            Permission::where('name', 'members.portal.contributions')->pluck('id')
        );

        $relatorioMenu = $portalMenu->children()->firstOrCreate(['title' => 'Minhas Contribuições'], [
            'url' => '/portal/contribuicoes',
            'icon' => 'TrendingUp',
            'order' => 1
        ]);

        $relatorioMenu->permissions()->sync(
            Permission::where('name', 'members.portal.contributions')->pluck('id')
        );
    }
}
