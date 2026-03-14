<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Garantir que a Role 'Admin' existe e tem tipo 'system'
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrador Total do Sistema', 'type' => 'system']
        );

        // 2. Sincronizar TODAS as permissões existentes com a Role Admin
        $permissions = Permission::all();
        $adminRole->permissions()->sync($permissions->pluck('id'));

        // 3. Criar ou Atualizar o usuário Super Admin
        $user = User::updateOrCreate(
            ['email' => 'admin@simonton.com.br'],
            [
                'name' => 'Super Admin Simonton',
                'password' => Hash::make('simonton123'),
                'role' => 'admin',
            ]
        );

        // 4. Atribuir a Role ao usuário se ele ainda não tiver
        if (!$user->roles->contains($adminRole->id)) {
            $user->roles()->attach($adminRole);
        }

        $this->command->info("--------------------------------------------------");
        $this->command->info("USUÁRIO SUPER ADMIN CRIADO COM SUCESSO!");
        $this->command->info("Email: admin@simonton.com.br");
        $this->command->info("Senha: simonton123");
        $this->command->info("Permissões: TODAS (" . $permissions->count() . " ativas)");
        $this->command->info("--------------------------------------------------");
    }
}
