<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$info = DB::select("PRAGMA table_info(patrimony_categories)");
foreach ($info as $col) {
    echo $col->name . " (notnull=" . $col->notnull . ")\n";
}
