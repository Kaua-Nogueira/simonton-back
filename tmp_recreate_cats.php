<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

echo "STARTING RECREATE patrimony_categories\n";

// 1. Get current data
$cats = DB::table('patrimony_categories')->get();
echo "Found " . $cats->count() . " categories\n";

// 2. Disable constraints
DB::statement('PRAGMA foreign_keys = OFF');

// 3. Drop old table
Schema::dropIfExists('patrimony_categories');

// 4. Create new table without prefix/last_counter
Schema::create('patrimony_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
echo "Recreated table without prefix/last_counter\n";

// 5. Restore data
foreach ($cats as $cat) {
    DB::table('patrimony_categories')->insert([
        'id' => $cat->id,
        'name' => $cat->name,
        'created_at' => $cat->created_at,
        'updated_at' => $cat->updated_at,
    ]);
}
echo "Restored data\n";

// 6. Enable constraints
DB::statement('PRAGMA foreign_keys = ON');

echo "FINISHED RECREATE patrimony_categories\n";
