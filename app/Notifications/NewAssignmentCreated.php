<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Group;
use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewAssignmentCreated extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $student;
    public $assignment;
    public $group;

    public function __construct(User $student, Assessment $assignment, Group $group)
    {
        $this->student = $student;
        $this->assignment = $assignment;
        $this->group = $group;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_assignment_created',
            'title' => 'واجب جديد',
            'message' => "تم إضافة واجب جديد ({$this->assignment->title}) لابنك/ابنتك {$this->student->name} في مجموعة {$this->group->name}",
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'assignment_id' => $this->assignment->id,
            'assignment_title' => $this->assignment->title,
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'due_date' => $this->assignment->due_date ?? null,
            'icon' => 'document-text',
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
