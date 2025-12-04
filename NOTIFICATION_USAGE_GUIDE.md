# Real-Time Notification System - Usage Guide

## Overview

This guide explains how to use the real-time notification system in your Laravel + Angular SAMS application.

## Laravel Backend

### 1. Sending Notifications

#### Case 1: New Center Admin Registration

```php
use App\Notifications\NewCenterAdminRegistration;
use App\Models\User;

// Get all admins
$admins = User::where('role', 'admin')->get();

// Notify all admins
foreach ($admins as $admin) {
    $admin->notify(new NewCenterAdminRegistration($centerAdmin));
}
```

#### Case 2: Center Admin Status Changed (Approved/Rejected)

```php
use App\Notifications\CenterAdminStatusChanged;

// Approve center admin
$centerAdmin->notify(new CenterAdminStatusChanged('approved'));

// Reject center admin with reason
$centerAdmin->notify(new CenterAdminStatusChanged('rejected', 'البيانات غير مكتملة'));
```

#### Case 3: Teacher Account Created (Email Only)

```php
use App\Notifications\TeacherAccountCreated;

$teacher->notify(new TeacherAccountCreated($plainPassword, $centerAdmin));
```

#### Case 4: Teacher Creates New Group

```php
use App\Notifications\NewGroupCreated;

// Notify center admin
$centerAdmin->notify(new NewGroupCreated($group, $teacher));
```

#### Case 5: Student Account Created

```php
use App\Notifications\StudentAccountCreated;

$student->notify(new StudentAccountCreated($plainPassword, $createdBy));
```

#### Case 6: Parent Account Created

```php
use App\Notifications\ParentAccountCreated;

$parent->notify(new ParentAccountCreated($plainPassword, $student));
```

#### Case 7: Group Updated

```php
use App\Notifications\GroupUpdated;

// Get all students in the group
$students = $group->students;

// Notify each student
foreach ($students as $student) {
    $student->notify(new GroupUpdated($group, [
        'name' => 'تم تغيير الاسم',
        'schedule' => 'تم تحديث الجدول'
    ]));
}
```

#### Case 8: Student Absent

```php
use App\Notifications\StudentAbsent;

// Get student's parents
$parents = $student->parents; // Assuming you have this relationship

foreach ($parents as $parent) {
    $parent->notify(new StudentAbsent($student, $group, now()->format('Y-m-d')));
}
```

#### Case 9: Student Late

```php
use App\Notifications\StudentLate;

// Get student's parents
$parents = $student->parents;

foreach ($parents as $parent) {
    $parent->notify(new StudentLate($student, $group, now()->format('Y-m-d'), 15)); // 15 minutes late
}
```

#### Case 10: New Assignment Created

```php
use App\Notifications\NewAssignmentCreated;

// Get all students in the group
$students = $group->students;

foreach ($students as $student) {
    // Notify student's parents
    $parents = $student->parents;
    foreach ($parents as $parent) {
        $parent->notify(new NewAssignmentCreated($student, $assignment, $group));
    }
}
```

### 2. Example Controller Implementation

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Notifications\NewCenterAdminRegistration;
use App\Notifications\CenterAdminStatusChanged;
use App\Notifications\NewGroupCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ExampleController extends Controller
{
    // When center admin registers
    public function registerCenterAdmin(Request $request)
    {
        $centerAdmin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'center_admin',
            'status' => 'pending',
        ]);

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewCenterAdminRegistration($centerAdmin));
        }

        return response()->json(['message' => 'تم التسجيل بنجاح']);
    }

    // When admin approves/rejects center admin
    public function updateCenterAdminStatus(Request $request, User $centerAdmin)
    {
        $status = $request->status; // 'approved' or 'rejected'
        $reason = $request->reason; // optional

        $centerAdmin->update(['status' => $status]);

        // Notify center admin
        $centerAdmin->notify(new CenterAdminStatusChanged($status, $reason));

        return response()->json(['message' => 'تم تحديث الحالة']);
    }

    // When teacher creates a group
    public function createGroup(Request $request)
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
            $centerAdmin->notify(new NewGroupCreated($group, $teacher));
        }

        return response()->json(['message' => 'تم إنشاء المجموعة']);
    }
}
```

### 3. Queue Configuration

Make sure your queue is running:

```bash
php artisan queue:work
```

Or use supervisor in production.

### 4. Broadcasting Configuration

Ensure your `.env` has:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=2086937
PUSHER_APP_KEY=f3a80187efd8663a3273
PUSHER_APP_SECRET=2d24344a7aac708222a7
PUSHER_APP_CLUSTER=mt1
```

### 5. Install Required Packages

```bash
composer require pusher/pusher-php-server
```

## Angular Frontend

### 1. The Notification Service is Auto-Initialized

The notification service automatically initializes when:

-   User logs in
-   App starts with authenticated user

### 2. Subscribe to Group Channels Dynamically

When a user joins a group or views group details:

```typescript
import { NotificationService } from "@core/services/notification.service";

export class GroupComponent implements OnInit {
    constructor(private notificationService: NotificationService) {}

    ngOnInit() {
        // Subscribe to this group's channel
        this.notificationService.subscribeToGroupChannel(this.groupId);
    }

    ngOnDestroy() {
        // Unsubscribe when leaving
        this.notificationService.unsubscribeFromGroupChannel(this.groupId);
    }
}
```

### 3. Manual Notification Loading

```typescript
// Load latest notifications
this.notificationService.loadNotifications();

// Load unread count
this.notificationService.loadUnreadCount();
```

### 4. Mark Notifications as Read

```typescript
// Mark single notification as read
this.notificationService.markAsRead(notificationId).subscribe();

// Mark all as read
this.notificationService.markAllAsRead().subscribe();
```

### 5. Access Notifications in Components

```typescript
import { NotificationService } from "@core/services/notification.service";

export class MyComponent implements OnInit {
    unreadCount$ = this.notificationService.unreadCount$;
    notifications$ = this.notificationService.notifications$;

    constructor(private notificationService: NotificationService) {}
}
```

## Testing

### Test Pusher Connection

1. Open browser console
2. Look for messages like:
    - ✅ Subscribed to user channel
    - ✅ Subscribed to admin channel
    - ✅ Subscribed to group X channel

### Test Notification Sending

Use Tinker:

```bash
php artisan tinker
```

```php
$user = User::find(1);
$centerAdmin = User::find(2);
$user->notify(new \App\Notifications\NewCenterAdminRegistration($centerAdmin));
```

### Debug Pusher

Visit: https://dashboard.pusher.com/apps/2086937/debug_console

You should see events being triggered.

## Troubleshooting

### Notifications not appearing in Angular

1. Check browser console for Pusher errors
2. Verify token is being sent in auth headers
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify broadcasting routes are working: `php artisan route:list | grep broadcasting`

### Pusher authentication failing

1. Ensure `routes/channels.php` is properly configured
2. Check that `BroadcastServiceProvider` is registered in `config/app.php`
3. Verify token is valid and user is authenticated

### Queue not processing

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear queue
php artisan queue:flush
```

## Production Checklist

-   [ ] Configure supervisor for queue workers
-   [ ] Set up Redis for queue driver (recommended)
-   [ ] Enable queue monitoring
-   [ ] Set up proper error logging
-   [ ] Configure rate limiting for notifications
-   [ ] Test all notification scenarios
-   [ ] Verify Pusher limits and upgrade plan if needed

## API Endpoints

```
GET    /api/notifications              - Get all notifications
GET    /api/notifications/unread-count - Get unread count
POST   /api/notifications/mark-all-read - Mark all as read
POST   /api/notifications/{id}/mark-read - Mark one as read
DELETE /api/notifications/{id}         - Delete notification
DELETE /api/notifications              - Delete all notifications
```

## Support

For issues or questions, contact the development team.
