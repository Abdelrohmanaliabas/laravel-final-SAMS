<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channell.
|
*/

// Private channel for individual users
Broadcast::channel('private-user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Private channel for all admins
Broadcast::channel('private-admin-channel', function ($user) {
    return $user->isAdmin();
});

// Private channel for specific group
Broadcast::channel('private-group.{groupId}', function ($user, $groupId) {
    // Check if user is a student in this group or the teacher
    return $user->groups()->where('groups.id', $groupId)->exists()
        || $user->taughtGroups()->where('groups.id', $groupId)->exists();
});
