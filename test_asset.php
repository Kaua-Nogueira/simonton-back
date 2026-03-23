<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$asset = \App\Models\Asset::find(51);
if ($asset) {
    echo "ASSET EXISTS: " . $asset->name;
    // Check if validation works
    $validator = \Illuminate\Support\Facades\Validator::make(['asset_id' => 51], ['asset_id' => 'required|exists:assets,id']);
    if ($validator->fails()) {
        echo " | BUT VALIDATOR FAILS: " . json_encode($validator->errors());
    } else {
        echo " | VALIDATOR PASSES";
    }
} else {
    echo "ASSET 51 DOES NOT EXIST IN DATABASE " . env('DB_DATABASE');
}
