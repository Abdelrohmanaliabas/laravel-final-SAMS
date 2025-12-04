<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewCenterAdminRegistration extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $centerAdmin;

    public function __construct(User $centerAdmin)
    {
        $this->centerAdmin = $centerAdmin;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_center_admin_registration',
            'title' => 'تسجيل مدير مركز جديد',
            'message' => "مدير مركز جديد ({$this->centerAdmin->name}) قام بالتسجيل وينتظر الموافقة",
            'center_admin_id' => $this->centerAdmin->id,
            'center_admin_name' => $this->centerAdmin->name,
            'center_admin_email' => $this->centerAdmin->email,
            'icon' => 'user-plus',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'new_center_admin_registration',
            'title' => 'تسجيل مدير مركز جديد',
            'message' => "مدير مركز جديد ({$this->centerAdmin->name}) قام بالتسجيل وينتظر الموافقة",
            'center_admin_id' => $this->centerAdmin->id,
            'center_admin_name' => $this->centerAdmin->name,
            'center_admin_email' => $this->centerAdmin->email,
            'icon' => 'user-plus',
            'created_at' => now()->toISOString(),
        ]);
    }

    public function broadcastOn(): array
    {
        return ['private-admin-channel'];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }
}
