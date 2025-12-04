<?php

/**
 * Quick Test Script for Notifications
 *
 * Run with: php tests/test_notifications.php
 *
 * Make sure to run this from the Laravel root directory
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Group;
use App\Notifications\NewCenterAdminRegistration;
use App\Notifications\CenterAdminStatusChanged;
use App\Notifications\NewGroupCreated;

echo "🧪 Testing Notification System\n";
echo "================================\n\n";

// Test 1: Check if users exist
echo "1️⃣ Checking users...\n";
$admin = User::where('role', 'admin')->first();
$centerAdmin = User::where('role', 'center_admin')->first();
$teacher = User::where('role', 'teacher')->first();
$student = User::where('role', 'student')->first();

if (!$admin) {
    echo "❌ No admin user found. Please create an admin user first.\n";
    exit(1);
}
echo "✅ Admin found: {$admin->name}\n";

if (!$centerAdmin) {
    echo "⚠️  No center admin found. Creating one for testing...\n";
    $centerAdmin = User::create([
        'name' => 'Test Center Admin',
        'email' => 'centeradmin@test.com',
        'password' => bcrypt('password'),
        'role' => 'center_admin',
        'status' => 'pending',
    ]);
    echo "✅ Center admin created: {$centerAdmin->name}\n";
} else {
    echo "✅ Center admin found: {$centerAdmin->name}\n";
}

// Test 2: Send notification to admin
echo "\n2️⃣ Sending notification to admin...\n";
try {
    $admin->notify(new NewCenterAdminRegistration($centerAdmin));
    echo "✅ Notification sent successfully!\n";
    echo "   Check your Angular app for the notification.\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}

// Test 3: Send status change notification
echo "\n3️⃣ Sending status change notification...\n";
try {
    $centerAdmin->notify(new CenterAdminStatusChanged('approved'));
    echo "✅ Status change notification sent!\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}

// Test 4: Check database
echo "\n4️⃣ Checking database...\n";
$notificationCount = \Illuminate\Support\Facades\DB::table('notifications')->count();
echo "✅ Total notifications in database: {$notificationCount}\n";

// Test 5: Check Pusher configuration
echo "\n5️⃣ Checking Pusher configuration...\n";
$pusherKey = config('broadcasting.connections.pusher.key');
$pusherCluster = config('broadcasting.connections.pusher.options.cluster');
if ($pusherKey && $pusherCluster) {
    echo "✅ Pusher configured:\n";
    echo "   Key: {$pusherKey}\n";
    echo "   Cluster: {$pusherCluster}\n";
} else {
    echo "❌ Pusher not configured properly\n";
}

// Test 6: Check queue configuration
echo "\n6️⃣ Checking queue configuration...\n";
$queueDriver = config('queue.default');
echo "✅ Queue driver: {$queueDriver}\n";
if ($queueDriver === 'sync') {
    echo "⚠️  Warning: Using 'sync' driver. For production, use 'database' or 'redis'\n";
}

echo "\n================================\n";
echo "✅ All tests completed!\n\n";
echo "📋 Next steps:\n";
echo "1. Make sure queue worker is running: php artisan queue:work\n";
echo "2. Open your Angular app and login\n";
echo "3. Check the notification bell icon\n";
echo "4. You should see the test notifications\n\n";
echo "🔍 Debug:\n";
echo "- Check Laravel logs: tail -f storage/logs/laravel.log\n";
echo "- Check Pusher debug console: https://dashboard.pusher.com/apps/2086937/debug_console\n";
echo "- Check browser console for Pusher connection status\n";
