<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$migrations = [
    '2026_03_25_214106_move_tombo_logic_to_locations',
    '2026_03_26_154137_add_booking_fields_to_patrimony_locations',
    '2026_03_26_195936_update_space_bookings_to_use_patrimony_locations'
];

foreach ($migrations as $m) {
    if (!DB::table('migrations')->where('migration', $m)->exists()) {
        DB::table('migrations')->insert(['migration' => $m, 'batch' => 99]);
        echo "Marked $m as done\n";
    }
}
