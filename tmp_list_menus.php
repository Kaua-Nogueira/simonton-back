<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$menus = \App\Models\Menu::whereNull('parent_id')->orderBy('order')->get(['id', 'title', 'order']);
foreach ($menus as $m) {
    echo "ID: {$m->id} | Title: {$m->title} | Order: {$m->order}\n";
}
