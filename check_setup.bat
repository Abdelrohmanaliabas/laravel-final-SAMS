@echo off
echo 🔍 Checking Real-Time Notification System Setup
echo ================================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ❌ Error: Please run this script from the Laravel root directory
    exit /b 1
)

echo ✅ Running from Laravel directory
echo.

REM Check .env file
echo 📋 Checking .env configuration...
findstr /C:"BROADCAST_CONNECTION=pusher" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ BROADCAST_CONNECTION is set to pusher
) else (
    echo ❌ BROADCAST_CONNECTION is not set to pusher
)

findstr /C:"PUSHER_APP_KEY=f3a80187efd8663a3273" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Pusher credentials configured
) else (
    echo ⚠️  Pusher credentials might not be configured
)
echo.

REM Check if Pusher package is installed
echo 📦 Checking Pusher package...
findstr /C:"pusher/pusher-php-server" composer.json >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Pusher PHP package is in composer.json
) else (
    echo ❌ Pusher PHP package not found. Run: composer require pusher/pusher-php-server
)
echo.

REM Check if notification files exist
echo 📁 Checking notification files...
set MISSING_FILES=0

if exist "app\Notifications\NewCenterAdminRegistration.php" (
    echo ✅ NewCenterAdminRegistration.php
) else (
    echo ❌ NewCenterAdminRegistration.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\CenterAdminStatusChanged.php" (
    echo ✅ CenterAdminStatusChanged.php
) else (
    echo ❌ CenterAdminStatusChanged.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\TeacherAccountCreated.php" (
    echo ✅ TeacherAccountCreated.php
) else (
    echo ❌ TeacherAccountCreated.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\NewGroupCreated.php" (
    echo ✅ NewGroupCreated.php
) else (
    echo ❌ NewGroupCreated.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\GroupUpdated.php" (
    echo ✅ GroupUpdated.php
) else (
    echo ❌ GroupUpdated.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist"app\Notifications\StudentAccountCreated.php" (
    echo ✅ StudentAccountCreated.php
) else (
    echo ❌ StudentAccountCreated.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\ParentAccountCreated.php" (
    echo ✅ ParentAccountCreated.php
) else (
    echo ❌ ParentAccountCreated.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\StudentAbsent.php" (
    echo ✅ StudentAbsent.php
) else (
    echo ❌ StudentAbsent.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\StudentLate.php" (
    echo ✅ StudentLate.php
) else (
    echo ❌ StudentLate.php ^(missing^)
    set /a MISSING_FILES+=1
)

if exist "app\Notifications\NewAssignmentCreated.php" (
    echo ✅ NewAssignmentCreated.php
) else (
    echo ❌ NewAssignmentCreated.php ^(missing^)
    set /a MISSING_FILES+=1
)
echo.

REM Check routes/channels.php
echo 🛣️  Checking routes...
if exist "routes\channels.php" (
    echo ✅ routes\channels.php exists
) else (
    echo ❌ routes\channels.php missing
)

if exist "app\Http\Controllers\NotificationController.php" (
    echo ✅ NotificationController exists
) else (
    echo ❌ NotificationController missing
)
echo.

REM Check if migration exists
echo 🗄️  Checking database...
if exist "database\migrations\2024_01_01_000001_create_notifications_table.php" (
    echo ✅ Notifications migration exists
) else (
    echo ❌ Notifications migration missing
)
echo.

REM Check config files
echo ⚙️  Checking configuration...
if exist "config\broadcasting.php" (
    echo ✅ config\broadcasting.php exists
) else (
    echo ❌ config\broadcasting.php missing
)

if exist "app\Providers\BroadcastServiceProvider.php" (
    echo ✅ BroadcastServiceProvider exists
) else (
    echo ❌ BroadcastServiceProvider missing
)
echo.

REM Summary
echo ================================================
if %MISSING_FILES% equ 0 (
    echo ✅ All notification files are present!
) else (
    echo ⚠️  %MISSING_FILES% notification file^(s^) missing
)
echo.

echo 📝 Next steps:
echo 1. Run: php artisan migrate
echo 2. Run: php artisan queue:work ^(in separate terminal^)
echo 3. Run: php artisan serve
echo 4. Test with: php tests\test_notifications.php
echo.
echo 🔍 Debug tools:
echo - Laravel logs: type storage\logs\laravel.log
echo - Pusher console: https://dashboard.pusher.com/apps/2086937/debug_console
echo - Queue status: php artisan queue:failed
echo.

pause

