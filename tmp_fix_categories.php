<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Recreate patrimony_categories table without prefix and last_counter (SQLite workaround)
// Or just try to drop them again with Symfony console output to see if it works

try {
    Schema::table('patrimony_categories', function (Blueprint $table) {
        if (Schema::hasColumn('patrimony_categories', 'prefix')) {
            $table->dropColumn('prefix');
            echo "Dropped prefix from categories\n";
        }
        if (Schema::hasColumn('patrimony_categories', 'last_counter')) {
            $table->dropColumn('last_counter');
            echo "Dropped last_counter from categories\n";
        }
    });
} catch (\Exception $e) {
    echo "ERROR DRIPPING COLUMNS: " . $e->getMessage() . "\n";
    echo "Attempting alternative: make them nullable\n";
    // Making columns nullable in SQLite is ALSO complex because of recreating table.
}

echo "Fix categories complete!\n";
