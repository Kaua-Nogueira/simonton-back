<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
echo "DB FULL PATH: " . $db . "\n";
echo "FILE SIZE: " . filesize($db) . " bytes\n";
echo "ASSETS COUNT: " . \App\Models\Asset::count() . "\n";
foreach(\App\Models\Asset::query()->take(10)->get() as $a) {
    echo "ID: $a->id, NAME: $a->name\n";
}
