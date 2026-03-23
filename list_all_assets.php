<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$assets = \App\Models\Asset::orderBy('id')->get();
echo "TOTAL ASSETS: " . $assets->count() . "\n";
foreach($assets as $a) {
    echo "ID: $a->id, NAME: $a->name\n";
}
