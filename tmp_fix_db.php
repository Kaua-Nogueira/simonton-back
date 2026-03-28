<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::table('patrimony_locations', function(Blueprint $table) {
    if(!Schema::hasColumn('patrimony_locations', 'capacity')) {
        $table->integer('capacity')->nullable();
        echo "Added capacity column\n";
    }
    if(!Schema::hasColumn('patrimony_locations', 'is_bookable')) {
        $table->boolean('is_bookable')->default(true);
        echo "Added is_bookable column\n";
    }
});

echo "Database fix complete!\n";
