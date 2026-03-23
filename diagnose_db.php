<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "DATABASE NAME: " . \Illuminate\Support\Facades\DB::connection()->getDatabaseName() . "\n";
echo "ASSETS COUNT: " . \App\Models\Asset::count() . "\n";
foreach(\App\Models\Asset::query()->take(10)->get() as $a) {
    echo "ID: $a->id, NAME: $a->name\n";
}
echo "MEMBERS COUNT: " . \App\Models\Member::count() . "\n";
foreach(\App\Models\Member::query()->take(10)->get() as $m) {
    echo "ID: $m->id, NAME: $m->name\n";
}
