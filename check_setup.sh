#!/bin/bash

echo "🔍 Checking Real-Time Notification System Setup"
echo "================================================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory"
    exit 1
fi

echo "✅ Running from Laravel directory"
echo ""

# Check .env file
echo "📋 Checking .env configuration..."
if grep -q "BROADCAST_CONNECTION=pusher" .env; then
    echo "✅ BROADCAST_CONNECTION is set to pusher"
else
    echo "❌ BROADCAST_CONNECTION is not set to pusher"
fi

if grep -q "PUSHER_APP_KEY=f3a80187efd8663a3273" .env; then
    echo "✅ Pusher credentials configured"
else
    echo "⚠️  Pusher credentials might not be configured"
fi
echo ""

# Check if Pusher package is installed
echo "📦 Checking Pusher package..."
if grep -q "pusher/pusher-php-server" composer.json; then
    echo "✅ Pusher PHP package is in composer.json"
else
    echo "❌ Pusher PHP package not found. Run: composer require pusher/pusher-php-server"
fi
echo ""

# Check if notification files exist
echo "📁 Checking notification files..."
NOTIFICATION_FILES=(
    "app/Notifications/NewCenterAdminRegistration.php"
    "app/Notifications/CenterAdminStatusChanged.php"
    "app/Notifications/TeacherAccountCreated.php"
    "app/Notifications/NewGroupCreated.php"
    "app/Notifications/GroupUpdated.php"
    "app/Notifications/StudentAccountCreated.php"
    "app/Notifications/ParentAccountCreated.php"
    "app/Notifications/StudentAbsent.php"
    "app/Notifications/StudentLate.php"
    "app/Notifications/NewAssignmentCreated.php"
)

MISSING_FILES=0
for file in "${NOTIFICATION_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (missing)"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done
echo ""

# Check routes/channels.php
echo "🛣️  Checking routes..."
if [ -f "routes/channels.php" ]; then
    echo "✅ routes/channels.php exists"
else
    echo "❌ routes/channels.php missing"
fi

if [ -f "app/Http/Controllers/NotificationController.php" ]; then
    echo "✅ NotificationController exists"
else
    echo "❌ NotificationController missing"
fi
echo ""

# Check if migration exists
echo "🗄️  Checking database..."
if [ -f "database/migrations/2024_01_01_000001_create_notifications_table.php" ]; then
    echo "✅ Notifications migration exists"
else
    echo "❌ Notifications migration missing"
fi

# Check if notifications table exists in database
php artisan migrate:status 2>/dev/null | grep -q "notifications" && echo "✅ Notifications table migrated" || echo "⚠️  Run: php artisan migrate"
echo ""

# Check config files
echo "⚙️  Checking configuration..."
if [ -f "config/broadcasting.php" ]; then
    echo "✅ config/broadcasting.php exists"
else
    echo "❌ config/broadcasting.php missing"
fi

if [ -f "app/Providers/BroadcastServiceProvider.php" ]; then
    echo "✅ BroadcastServiceProvider exists"
else
    echo "❌ BroadcastServiceProvider missing"
fi
echo ""

# Summary
echo "================================================"
if [ $MISSING_FILES -eq 0 ]; then
    echo "✅ All notification files are present!"
else
    echo "⚠️  $MISSING_FILES notification file(s) missing"
fi
echo ""

echo "📝 Next steps:"
echo "1. Run: php artisan migrate"
echo "2. Run: php artisan queue:work (in separate terminal)"
echo "3. Run: php artisan serve"
echo "4. Test with: php tests/test_notifications.php"
echo ""
echo "🔍 Debug tools:"
echo "- Laravel logs: tail -f storage/logs/laravel.log"
echo "- Pusher console: https://dashboard.pusher.com/apps/2086937/debug_console"
echo "- Queue status: php artisan queue:failed"

