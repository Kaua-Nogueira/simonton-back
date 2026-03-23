<?php

use App\Models\Menu;
use App\Models\Role;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Delete existing items to avoid duplicates during this fix
Menu::where('url', 'like', '/patrimonio%')->delete();

$parent = Menu::create([
    'title' => 'Patrimônio',
    'url' => '/patrimonio',
    'icon' => 'PackageOpen',
    'order' => 12
]);

echo "Created parent: ID {$parent->id}\n";

$children = [
    ['title' => 'Resumo', 'url' => '/patrimonio', 'icon' => 'LayoutDashboard', 'order' => 1],
    ['title' => 'Inventário', 'url' => '/patrimonio/inventario', 'icon' => 'Box', 'order' => 2],
    ['title' => 'Cadastros', 'url' => '/patrimonio/cadastros', 'icon' => 'Settings', 'order' => 3],
    ['title' => 'Manutenção', 'url' => '/patrimonio/manutencao', 'icon' => 'Clock', 'order' => 4],
    ['title' => 'Empréstimos', 'url' => '/patrimonio/emprestimos', 'icon' => 'History', 'order' => 5],
    ['title' => 'Agenda / Espaços', 'url' => '/patrimonio/locais', 'icon' => 'BookOpen', 'order' => 6],
];

foreach ($children as $child) {
    $c = Menu::create(array_merge($child, ['parent_id' => $parent->id]));
    echo "Created child: {$child['title']} (ID {$c->id})\n";
}

$roles = Role::all();
foreach (Menu::where('url', 'like', '/patrimonio%')->get() as $menu) {
    $menu->roles()->sync($roles->pluck('id'));
}

echo "Synced roles for all patrimony menus.\n";
