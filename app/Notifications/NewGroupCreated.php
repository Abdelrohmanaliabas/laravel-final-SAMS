<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewGroupCreated extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $group;
    public $teacher;

    public function __construct(Group $group, User $teacher)
    {
        $this->group = $group;
        $this->teacher = $teacher;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_group_created',
            'title' => 'مجموعة جديدة تم إنشاؤها',
            'message' => "المعلم {$this->teacher->name} قام بإنشاء مجموعة جديدة: {$this->group->name}",
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_subject' => $this->group->subject,
            'teacher_id' => $this->teacher->id,
            'teacher_name' => $this->teacher->name,
            'icon' => 'user-group',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastOn(): array
    {
        return ['private-user.' . $notifiable->id];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }
}
