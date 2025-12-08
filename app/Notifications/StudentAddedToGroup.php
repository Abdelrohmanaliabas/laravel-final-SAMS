<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StudentAddedToGroup extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $addedBy, public ?Group $group = null)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $groupName = $this->group?->name;

        return [
            'type' => 'student_added_to_group',
            'title' => 'You have been added to a group',
            'message' => $groupName
                ? "You have been added to the group {$groupName} by {$this->addedBy->name}."
                : "You have been added to a group by {$this->addedBy->name}.",
            'group_id' => $this->group?->id,
            'group_name' => $groupName,
            'added_by' => [
                'id' => $this->addedBy->id,
                'name' => $this->addedBy->name,
                'role' => $this->addedBy->getRoleNames()->first(),
            ],
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Determine if notification should be sent after database transaction commits.
     */
    public function afterCommit(): bool
    {
        return true;
    }
}
