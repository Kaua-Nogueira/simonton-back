<?php
use App\Models\User;
use App\Notifications\NewDiaconiaConference;
use App\Models\TreasuryEntry;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mock entry
$entry = new TreasuryEntry();
$entry->id = 999;
$entry->total_amount = 123.45;

$users = User::all();
echo "Sending to " . $users->count() . " users...\n";
\Illuminate\Support\Facades\Notification::send($users, new NewDiaconiaConference($entry));
echo "Sent.";
