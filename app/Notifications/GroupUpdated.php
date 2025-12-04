<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GroupUpdated extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $group;
    public $changes;

    public function __construct(Group $group, array $changes)
    {
        $this->group = $group;
        $this->changes = $changes;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        $changesList = $this->formatChanges();

        return [
            'type' => 'group_updated',
            'title' => 'تحديث في المجموعة',
            'message' => "تم تحديث معلومات المجموعة {$this->group->name}: {$changesList}",
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'changes' => $this->changes,
            'icon' => 'pencil-square',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastOn(): array
    {
        return ['private-group.' . $this->group->id];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    private function formatChanges(): string
    {
        $formatted = [];
        $labels = [
            'name' => 'الاسم',
            'description' => 'الوصف',
            'subject' => 'المادة',
            'schedule' => 'الجدول',
        ];

        foreach ($this->changes as $key => $value) {
            if (isset($labels[$key])) {
                $formatted[] = $labels[$key];
            }
        }

        return implode('، ', $formatted);
    }
}
