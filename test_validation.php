<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = \App\Models\Asset::latest()->first()?->id;

echo "LATEST ASSET ID: " . ($id ?? 'NONE') . "\n";

if ($id) {
    $data = [
        'asset_id' => $id,
        'requester_name' => 'Teste IA',
        'checkout_date' => '2026-03-23'
    ];
    $rules = [
        'asset_id' => 'required|exists:assets,id',
        'requester_name' => 'required|string',
        'checkout_date' => 'required|date'
    ];
    
    $v = \Illuminate\Support\Facades\Validator::make($data, $rules);
    if ($v->fails()) {
        echo "VALIDATION FAILS: " . json_encode($v->errors()) . "\n";
    } else {
        echo "VALIDATION PASSES\n";
    }

    try {
        $loan = \App\Models\AssetLoan::create($data);
        echo "LOAN CREATED SUCCESSFULLY: ID " . $loan->id . "\n";
    } catch (\Exception $e) {
        echo "CREATE ERROR: " . $e->getMessage() . "\n";
    }
}
