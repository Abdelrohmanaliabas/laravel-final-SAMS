<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StudentLate extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $student;
    public $group;
    public $date;
    public $minutesLate;

    public function __construct(User $student, Group $group, string $date, int $minutesLate)
    {
        $this->student = $student;
        $this->group = $group;
        $this->date = $date;
        $this->minutesLate = $minutesLate;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'student_late',
            'title' => 'تأخر الطالب',
            'message' => "الطالب {$this->student->name} تأخر {$this->minutesLate} دقيقة في مجموعة {$this->group->name} بتاريخ {$this->date}",
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'date' => $this->date,
            'minutes_late' => $this->minutesLate,
            'icon' => 'clock',
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
