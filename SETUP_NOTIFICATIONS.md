# Real-Time Notifications Setup Guide

## Quick Setup (5 Minutes)

### Laravel Backend Setup

1. **Run Migration**

```bash
cd laravel-final-SAMS
php artisan migrate
```

2. **Install Pusher PHP SDK** (if not already installed)

```bash
composer require pusher/pusher-php-server
```

3. **Verify .env Configuration**
   Your `.env` already has Pusher configured:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=2086937
PUSHER_APP_KEY=f3a80187efd8663a3273
PUSHER_APP_SECRET=2d24344a7aac708222a7
PUSHER_APP_CLUSTER=mt1
```

4. **Start Queue Worker**

```bash
php artisan queue:work
```

5. **Start Laravel Server**

```bash
php artisan serve
```

### Angular Frontend Setup

1. **Install Dependencies** (already installed)

```bash
cd angular-final-SAMS
npm install
# pusher-js, ngx-toastr, @angular/animations are already in package.json
```

2. **Start Angular Dev Server**

```bash
ng serve
```

## Verify Installation

### Test 1: Check Pusher Connection

1. Login to your Angular app
2. Open browser console (F12)
3. Look for these messages:
    ```
    ✅ Subscribed to user channel
    ✅ Subscribed to admin channel (if admin)
    ```

### Test 2: Send Test Notification

Open Laravel Tinker:

```bash
php artisan tinker
```

Send a test notification:

```php
$user = \App\Models\User::first();
$centerAdmin = \App\Models\User::where('role', 'center_admin')->first();

// Notify user
$user->notify(new \App\Notifications\NewCenterAdminRegistration($centerAdmin));
```

You should see:

-   Toast notification in Angular app
-   Bell icon badge increment
-   Notification in dropdown

## Integration Examples

### Example 1: When Center Admin Registers

In your `AuthController@register`:

```php
public function register(Request $request)
{
    // ... validation ...

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'center_admin',
        'status' => 'pending',
    ]);

    // Send activation email
    // ... existing code ...

    // Notify all admins
    $admins = User::where('role', 'admin')->get();
    foreach ($admins as $admin) {
        $admin->notify(new \App\Notifications\NewCenterAdminRegistration($user));
    }

    return response()->json([
        'message' => 'تم التسجيل بنجاح',
        'user' => $user
    ]);
}
```

### Example 2: When Admin Approves Center Admin

```php
public function approveCenterAdmin(User $centerAdmin)
{
    $centerAdmin->update(['status' => 'approved']);

    // Send notification
    $centerAdmin->notify(new \App\Notifications\CenterAdminStatusChanged('approved'));

    return response()->json(['message' => 'تم قبول مدير المركز']);
}
```

### Example 3: When Teacher Creates Group

```php
public function store(Request $request)
{
    $teacher = auth()->user();

    $group = Group::create([
        'name' => $request->name,
        'teacher_id' => $teacher->id,
        'center_id' => $teacher->center_id,
        // ... other fields
    ]);

    // Notify center admin
    $centerAdmin = User::where('center_id', $teacher->center_id)
        ->where('role', 'center_admin')
        ->first();

    if ($centerAdmin) {
        $centerAdmin->notify(new \App\Notifications\NewGroupCreated($group, $teacher));
    }

    return response()->json(['group' => $group]);
}
```

### Example 4: When Group is Updated

```php
public function update(Request $request, Group $group)
{
    $changes = [];

    if ($request->has('name') && $request->name !== $group->name) {
        $changes['name'] = $request->name;
    }

    if ($request->has('schedule') && $request->schedule !== $group->schedule) {
        $changes['schedule'] = $request->schedule;
    }

    $group->update($request->all());

    // Notify all students in the group
    if (!empty($changes)) {
        $students = $group->students;
        foreach ($students as $student) {
            $student->notify(new \App\Notifications\GroupUpdated($group, $changes));
        }
    }

    return response()->json(['group' => $group]);
}
```

### Example 5: When Student is Absent

```php
public function markAttendance(Request $request)
{
    $attendance = Attendance::create([
        'student_id' => $request->student_id,
        'group_id' => $request->group_id,
        'date' => $request->date,
        'status' => $request->status, // 'present', 'absent', 'late'
    ]);

    $student = User::find($request->student_id);
    $group = Group::find($request->group_id);

    // Notify parents if student is absent or late
    if ($request->status === 'absent') {
        $parents = $student->parents; // Assuming relationship exists
        foreach ($parents as $parent) {
            $parent->notify(new \App\Notifications\StudentAbsent($student, $group, $request->date));
        }
    } elseif ($request->status === 'late') {
        $parents = $student->parents;
        foreach ($parents as $parent) {
            $parent->notify(new \App\Notifications\StudentLate($student, $group, $request->date, $request->minutes_late ?? 0));
        }
    }

    return response()->json(['attendance' => $attendance]);
}
```

### Example 6: When Assignment is Created

```php
public function createAssignment(Request $request)
{
    $assignment = Assessment::create([
        'title' => $request->title,
        'group_id' => $request->group_id,
        'due_date' => $request->due_date,
        // ... other fields
    ]);

    $group = Group::find($request->group_id);
    $students = $group->students;

    // Notify each student's parents
    foreach ($students as $student) {
        $parents = $student->parents;
        foreach ($parents as $parent) {
            $parent->notify(new \App\Notifications\NewAssignmentCreated($student, $assignment, $group));
        }
    }

    return response()->json(['assignment' => $assignment]);
}
```

## Angular Integration

### Subscribe to Group Channels

When user enters a group page:

```typescript
// group-detail.component.ts
import { Component, OnInit, OnDestroy } from "@angular/core";
import { NotificationService } from "@core/services/notification.service";

export class GroupDetailComponent implements OnInit, OnDestroy {
    groupId: number;

    constructor(private notificationService: NotificationService) {}

    ngOnInit() {
        // Subscribe to this group's notifications
        this.notificationService.subscribeToGroupChannel(this.groupId);
    }

    ngOnDestroy() {
        // Clean up subscription
        this.notificationService.unsubscribeFromGroupChannel(this.groupId);
    }
}
```

### Display Notifications in Custom Component

```typescript
import { Component, OnInit } from "@angular/core";
import { NotificationService } from "@core/services/notification.service";

export class NotificationsPageComponent implements OnInit {
    notifications$ = this.notificationService.notifications$;
    unreadCount$ = this.notificationService.unreadCount$;

    constructor(private notificationService: NotificationService) {}

    ngOnInit() {
        this.notificationService.loadNotifications();
    }

    markAsRead(notificationId: string) {
        this.notificationService.markAsRead(notificationId).subscribe(() => {
            this.notificationService.loadNotifications();
            this.notificationService.loadUnreadCount();
        });
    }
}
```

## Troubleshooting

### Issue: Notifications not appearing

**Solution:**

1. Check browser console for errors
2. Verify Pusher credentials in `.env`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Verify queue is running: `php artisan queue:work`

### Issue: "401 Unauthorized" in Pusher auth

**Solution:**

1. Verify user is logged in
2. Check token is being sent in Authorization header
3. Verify `routes/channels.php` authorization logic

### Issue: Queue jobs not processing

**Solution:**

```bash
# Check failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Restart queue worker
php artisan queue:restart
```

### Issue: CORS errors

**Solution:**
Add to `config/cors.php`:

```php
'paths' => ['api/*', 'broadcasting/auth'],
```

## Production Deployment

### 1. Use Redis for Queue

Update `.env`:

```env
QUEUE_CONNECTION=redis
```

### 2. Setup Supervisor

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/laravel-final-SAMS/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/laravel-final-SAMS/storage/logs/worker.log
```

### 3. Monitor Queue

```bash
php artisan queue:monitor
```

## Complete File Checklist

### Laravel Files Created:

-   ✅ `routes/channels.php`
-   ✅ `config/broadcasting.php`
-   ✅ `app/Notifications/NewCenterAdminRegistration.php`
-   ✅ `app/Notifications/CenterAdminStatusChanged.php`
-   ✅ `app/Notifications/TeacherAccountCreated.php`
-   ✅ `app/Notifications/NewGroupCreated.php`
-   ✅ `app/Notifications/GroupUpdated.php`
-   ✅ `app/Notifications/StudentAccountCreated.php`
-   ✅ `app/Notifications/ParentAccountCreated.php`
-   ✅ `app/Notifications/StudentAbsent.php`
-   ✅ `app/Notifications/StudentLate.php`
-   ✅ `app/Notifications/NewAssignmentCreated.php`
-   ✅ `app/Http/Controllers/NotificationController.php`
-   ✅ `app/Models/Notification.php` (updated)
-   ✅ `database/migrations/2024_01_01_000001_create_notifications_table.php`
-   ✅ `routes/api.php` (updated)
-   ✅ `.env` (updated BROADCAST_CONNECTION)

### Angular Files Created:

-   ✅ `src/app/core/services/notification.service.ts`
-   ✅ `src/app/shared/notification-bell/notification-bell.component.ts`
-   ✅ `src/app/shared/notification-bell/notification-bell.component.html`
-   ✅ `src/app/shared/notification-bell/notification-bell.component.css`
-   ✅ `src/app/shared/header/header.ts` (updated)
-   ✅ `src/app/shared/header/header.html` (updated)
-   ✅ `src/app/app.ts` (updated)

## Next Steps

1. Run migrations: `php artisan migrate`
2. Start queue worker: `php artisan queue:work`
3. Test with Tinker
4. Integrate into your existing controllers
5. Test all 7 notification scenarios

## Support

For questions or issues, refer to:

-   `NOTIFICATION_USAGE_GUIDE.md` for detailed usage
-   Laravel Broadcasting docs: https://laravel.com/docs/broadcasting
-   Pusher docs: https://pusher.com/docs
