<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('name', 'calendar.events.index')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'calendar.events.index',
                'display_name' => 'Agenda Eclesiástica: Visualizar Lista',
                'group' => 'Agenda Eclesiástica',
                'method' => 'GET',
                'description' => 'Permissão para visualizar agenda eclesiástica.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $secretariaId = DB::table('menus')->where('title', 'Secretaria')->value('id');
        if (!$secretariaId) {
            $secretariaId = DB::table('menus')->insertGetId([
                'title' => 'Secretaria',
                'url' => '#',
                'icon' => 'BookOpen',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $menuId = DB::table('menus')
            ->where('title', 'Agenda Eclesiástica')
            ->where('parent_id', $secretariaId)
            ->value('id');

        if (!$menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'title' => 'Agenda Eclesiástica',
                'url' => '/agenda',
                'icon' => 'Calendar',
                'parent_id' => $secretariaId,
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionId = DB::table('permissions')->where('name', 'calendar.events.index')->value('id');
        if ($permissionId) {
            $exists = DB::table('menu_permission')
                ->where('menu_id', $menuId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$exists) {
                DB::table('menu_permission')->insert([
                    'menu_id' => $menuId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')
            ->where('title', 'Agenda Eclesiástica')
            ->where('url', '/agenda')
            ->value('id');

        if ($menuId) {
            DB::table('menu_permission')->where('menu_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
    }
};
