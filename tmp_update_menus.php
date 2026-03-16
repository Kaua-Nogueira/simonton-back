<?php

use App\Models\Menu;

echo "Updating menu 16...\n";
$m = Menu::find(16);
if ($m) {
    $m->update([
        'url' => null,
        'icon' => 'Banknote'
    ]);
    echo "Parent updated.\n";
}

echo "Creating children...\n";
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

echo "Done.\n";
