<?php

use App\Models\Asset;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Asset Logging...\n";

// 1. Create
$asset = Asset::create([
    'name' => 'Teste Log ' . time(),
    'code' => 'TL-' . rand(100, 999),
    'status' => 'available',
    'value' => 123.45,
    'description' => 'Teste'
]);

echo "Asset created with ID: " . $asset->id . "\n";

// 2. Update
$asset->update(['name' => 'Teste Log Updated']);
echo "Asset updated.\n";

// 3. Delete
$asset->delete();
echo "Asset deleted.\n";

// 4. Check Logs
$logs = AuditLog::where('auditable_type', Asset::class)
    ->where('auditable_id', $asset->id)
    ->get();

echo "Total logs found for this asset: " . $logs->count() . "\n";

foreach ($logs as $log) {
    echo "Action: {$log->action}\n";
    echo "Old Values: " . json_encode($log->old_values) . "\n";
    echo "New Values: " . json_encode($log->new_values) . "\n\n";
}
